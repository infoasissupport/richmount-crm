<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_view_quotations_index_and_filter_tabs(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        // View index
        $response = $this->get('/quotations');
        $response->assertStatus(200);
        $response->assertSee('Quotations');
        $response->assertSee('QT-DXB-2026-001');

        // View pending tab
        $responsePending = $this->get('/quotations?tab=pending');
        $responsePending->assertStatus(200);
        $responsePending->assertSee('QT-DXB-2026-002');
    }

    public function test_can_open_quotation_builder(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        $quote = Quotation::first();

        $response = $this->get('/quotations/builder?id=' . $quote->id);
        $response->assertStatus(200);
        $response->assertSee('Builder');
        $response->assertSee($quote->quote_number);
    }

    public function test_builder_redirects_to_lead_creation_when_no_leads_exist(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        // Clear leads and quotations in test DB
        Quotation::query()->delete();
        Lead::query()->delete();

        $response = $this->get('/quotations/builder');
        $response->assertRedirect(route('leads.index', ['new' => 1]));
        $response->assertSessionHas('toast_info');
    }

    public function test_create_draft_quotation_and_autosave(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        $lead = Lead::where('stage', 'new')->first();

        // 1. Create quotation via store
        $response = $this->post('/quotations', [
            'lead_id' => $lead->id,
            'branch_id' => $lead->branch_id,
            'validity_days' => 30,
            'output_type' => 'tax',
            'discount_type' => 'amt',
            'discount_value' => 1000.00,
            'technical_description' => 'Acoustic wall panelling specification test',
            'items' => [
                [
                    'description' => 'Fabric wrapped acoustic panel 1200x600mm',
                    'quantity' => 100,
                    'unit' => 'm²',
                    'unit_price' => 250.00,
                ],
                [
                    'description' => 'Anodized aluminium edge trims',
                    'quantity' => 50,
                    'unit' => 'm',
                    'unit_price' => 40.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $createdQuote = Quotation::where('lead_id', $lead->id)->latest('id')->first();
        $this->assertNotNull($createdQuote);
        $this->assertEquals(2, $createdQuote->lineItems()->count());

        // Subtotal = (100 * 250) + (50 * 40) = 25000 + 2000 = 27000
        // Discount = 1000 => Taxable = 26000
        // VAT 5% = 1300 => Grand total = 27300
        $this->assertEquals(27000.00, (float) $createdQuote->subtotal);
        $this->assertEquals(1000.00, (float) $createdQuote->discount_amount);
        $this->assertEquals(26000.00, (float) $createdQuote->taxable_amount);
        $this->assertEquals(1300.00, (float) $createdQuote->vat_amount);
        $this->assertEquals(27300.00, (float) $createdQuote->grand_total);

        // 2. Test autosave endpoint
        $autosaveResponse = $this->postJson("/quotations/{$createdQuote->id}/autosave", [
            'discount_type' => 'amt',
            'discount_value' => 2000.00,
            'output_type' => 'tax',
            'items' => [
                [
                    'description' => 'Fabric wrapped acoustic panel 1200x600mm',
                    'quantity' => 100,
                    'unit' => 'm²',
                    'unit_price' => 250.00,
                ],
            ],
        ]);

        $autosaveResponse->assertStatus(200);
        $autosaveResponse->assertJson(['success' => true]);

        $freshQuote = $createdQuote->fresh();
        // Subtotal = 25000, Discount = 2000 => Taxable = 23000 => VAT = 1150 => Grand Total = 24150
        $this->assertEquals(25000.00, (float) $freshQuote->subtotal);
        $this->assertEquals(2000.00, (float) $freshQuote->discount_amount);
        $this->assertEquals(23000.00, (float) $freshQuote->taxable_amount);
        $this->assertEquals(1150.00, (float) $freshQuote->vat_amount);
        $this->assertEquals(24150.00, (float) $freshQuote->grand_total);
    }

    public function test_clone_quotation_duplicates_line_items_with_new_number(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        $original = Quotation::where('status', 'draft')->with('lineItems')->first();
        $originalLineCount = $original->lineItems->count();

        $response = $this->post("/quotations/{$original->id}/clone");
        $response->assertRedirect();

        $clone = Quotation::latest('id')->first();
        $this->assertNotEquals($original->quote_number, $clone->quote_number);
        $this->assertEquals('draft', $clone->status);
        $this->assertEquals($originalLineCount, $clone->lineItems()->count());
    }

    public function test_full_quotation_approval_workflow(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $estimator = User::where('role_name', 'estimation')->first();

        $quote = Quotation::where('status', 'draft')->first();
        $lead = $quote->lead;

        // 1. Submit for approval
        $this->actingAs($estimator);
        $submitResponse = $this->post("/quotations/{$quote->id}/submit");
        $submitResponse->assertRedirect();
        $this->assertEquals('pending', $quote->fresh()->status);

        // 2. Approve quotation
        $this->actingAs($superAdmin);
        $approveResponse = $this->post("/quotations/{$quote->id}/approve");
        $approveResponse->assertRedirect();
        $this->assertEquals('approved', $quote->fresh()->status);
        $this->assertEquals($superAdmin->id, $quote->fresh()->approved_by_id);

        // 3. Send quotation to customer
        $sendResponse = $this->post("/quotations/{$quote->id}/send");
        $sendResponse->assertRedirect();
        $this->assertEquals('sent', $quote->fresh()->status);

        // Verify Lead status transitioned to Closed (engineer cannot see anymore)
        $this->assertEquals('sent', $lead->fresh()->stage);
        $this->assertEquals('Closed', $lead->fresh()->status);

        // 4. Customer awards quotation
        $awardResponse = $this->post("/quotations/{$quote->id}/award");
        $awardResponse->assertRedirect();
        $this->assertEquals('awarded', $quote->fresh()->status);
        $this->assertNotNull($quote->fresh()->award_date);

        // Verify Lead status transitioned to Won
        $this->assertEquals('awarded', $lead->fresh()->stage);
        $this->assertEquals('Won', $lead->fresh()->status);
    }

    public function test_reject_quotation_requires_reason(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'pending')->first();

        // Without reason -> validation error
        $responseFail = $this->post("/quotations/{$quote->id}/reject", []);
        $responseFail->assertSessionHasErrors('reason');

        // With reason -> rejected
        $responseOk = $this->post("/quotations/{$quote->id}/reject", [
            'reason' => 'Gross margin is below 18%. Please revise material costs with supplier.',
        ]);
        $responseOk->assertRedirect();
        $this->assertEquals('rejected', $quote->fresh()->status);
        $this->assertEquals('Gross margin is below 18%. Please revise material costs with supplier.', $quote->fresh()->rejection_reason);
    }

    public function test_pdf_export_for_all_three_branch_formats(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        // Format 1 (Dubai Branch)
        $quoteDxb = Quotation::where('branch_id', 1)->first();
        if ($quoteDxb) {
            $response = $this->get("/quotations/{$quoteDxb->id}/pdf");
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/pdf');
        }

        // Format 2 (Abu Dhabi Branch)
        $quoteAuh = Quotation::where('branch_id', 2)->first();
        if ($quoteAuh) {
            $response = $this->get("/quotations/{$quoteAuh->id}/pdf");
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/pdf');
        }

        // Format 3 (Sharjah Branch)
        $quoteShj = Quotation::where('branch_id', 3)->first();
        if ($quoteShj) {
            $response = $this->get("/quotations/{$quoteShj->id}/pdf");
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_mobile_api_quotation_endpoints(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $token = $estimator->createToken('flutter-test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/quotations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'quote_number', 'status', 'grand_total', 'lead'],
                ],
            ],
        ]);
    }
}
