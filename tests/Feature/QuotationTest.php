<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\QuotationAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $response = $this->get('/quotations/builder?id='.$quote->id);
        $response->assertStatus(200);
        $response->assertSee('Builder');
        $response->assertSee($quote->quote_number);
    }

    public function test_pending_quotation_builder_renders_request_revision_modal(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'pending')->firstOrFail();

        $response = $this->get('/quotations/builder?id='.$quote->id);

        $response->assertOk();
        $response->assertSee('onclick="openRejectModal()"', false);
        $response->assertSee('Return '.$quote->quote_number.' for revision?');
        $response->assertSee('Reason');
        $response->assertSee('Request Revision');
        $response->assertSee('onclick="openApproveModal()"', false);
        $response->assertSee('Approve '.$quote->quote_number.'?');
        $response->assertSee('Grand total <b>AED '.number_format($quote->grand_total, 2).'</b>', false);
        $response->assertSee('Once approved the quotation can be sent to the customer.');
    }

    public function test_sent_quotation_builder_renders_award_confirmation_modal(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'sent')->firstOrFail();

        $response = $this->get('/quotations/builder?id='.$quote->id);

        $response->assertOk();
        $response->assertSee('onclick="openAwardModal()"', false);
        $response->assertSee('Mark quotation as awarded?');
        $response->assertSee('Quote <b>#'.$quote->quote_number.'</b>', false);
        $response->assertSee('AED '.number_format($quote->grand_total, 2));
        $response->assertSee('Project creation becomes available.');
        $response->assertSee('Mark as Awarded');
        $response->assertSee(route('quotations.award', $quote), false);
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

    public function test_grouped_descriptions_keep_one_shared_amount_when_saved_edited_and_printed(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);
        $lead = Lead::where('stage', 'new')->first();

        $this->post('/quotations', [
            'lead_id' => $lead->id,
            'validity_days' => 30,
            'output_type' => 'tax',
            'items' => [
                ['description' => 'Mobilization and demobilization', 'quantity' => 1, 'unit' => 'LSM', 'unit_price' => 3000],
                ['type' => 'subdesc', 'description' => 'TARKHEES approval for slab cutting'],
                ['type' => 'subdesc', 'description' => 'Additional description 3'],
                ['type' => 'subdesc', 'description' => 'Additional description 4'],
                ['description' => 'Independent item', 'quantity' => 2, 'unit' => 'EA', 'unit_price' => 250],
            ],
        ])->assertRedirect();

        $quote = Quotation::where('lead_id', $lead->id)->latest('id')->firstOrFail();
        $this->assertSame(3, $quote->lineItems->where('type', 'subdesc')->count());
        $this->assertSame(['item', 'subdesc', 'subdesc', 'subdesc', 'item'], $quote->lineItems->pluck('type')->all());
        $this->assertEquals(3500.00, (float) $quote->subtotal);
        $this->assertEquals(3675.00, (float) $quote->grand_total);

        $this->get(route('quotations.builder', ['id' => $quote->id]))
            ->assertOk()
            ->assertSee('rowspan="4"', false);

        $this->get(route('quotations.document', $quote))
            ->assertOk()
            ->assertSee('rowspan="4"', false)
            ->assertSee('Additional description 4')
            ->assertSee('3,000.00')
            ->assertSee('500.00');

        $branch = $quote->branch;
        foreach (['format_1' => 'tax', 'format_2' => 'notax', 'format_3' => 'tech'] as $format => $mode) {
            $branch->forceFill(['quote_format' => $format])->save();
            $pdf = $this->get(route('quotations.pdf', ['quotation' => $quote, 'output' => $mode]));
            $pdf->assertOk();
            $this->assertStringStartsWith('%PDF', $pdf->getContent());
        }

        $this->postJson(route('quotations.autosave', $quote), [
            'items' => [
                ['description' => 'Updated mobilization scope', 'quantity' => 1, 'unit' => 'LSM', 'unit_price' => 3000],
                ['type' => 'subdesc', 'description' => 'Retained additional description'],
                ['description' => 'Independent item', 'quantity' => 2, 'unit' => 'EA', 'unit_price' => 250],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $quote->refresh();
        $this->assertSame(1, $quote->lineItems->where('type', 'subdesc')->count());
        $this->assertEquals(3500.00, (float) $quote->subtotal);
        $this->assertEquals(3675.00, (float) $quote->grand_total);

        $this->get(route('quotations.document', $quote))
            ->assertOk()
            ->assertSee('rowspan="2"', false)
            ->assertSee('Updated mobilization scope')
            ->assertDontSee('Additional description 4');

        $this->postJson(route('quotations.autosave', $quote), [
            'items' => [
                ['description' => 'Single-description group', 'quantity' => 1, 'unit' => 'LSM', 'unit_price' => 3000],
            ],
        ])->assertOk();

        $quote->refresh();
        $this->assertSame(0, $quote->lineItems->where('type', 'subdesc')->count());
        $this->assertEquals(3000.00, (float) $quote->subtotal);
        $this->get(route('quotations.document', $quote))
            ->assertOk()
            ->assertSee('rowspan="1"', false)
            ->assertSee('Single-description group');
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

    public function test_clone_quotation_for_different_target_lead(): void
    {
        $estimator = User::where('role_name', 'estimation')->first();
        $this->actingAs($estimator);

        $original = Quotation::where('status', 'draft')->with('lineItems')->first();
        $targetLead = Lead::where('id', '!=', $original->lead_id)->first();

        $response = $this->post("/quotations/{$original->id}/clone", [
            'lead_id' => $targetLead->id,
        ]);
        $response->assertRedirect();

        $clone = Quotation::latest('id')->first();
        $this->assertEquals($targetLead->id, $clone->lead_id);
        $this->assertEquals($targetLead->branch_id, $clone->branch_id);
        $this->assertNotEquals($original->quote_number, $clone->quote_number);
        $this->assertEquals('draft', $clone->status);
        $this->assertEquals($original->lineItems->count(), $clone->lineItems()->count());
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

    public function test_reopen_approved_quotation_moves_to_draft(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'draft')->first();
        $quote->update([
            'status' => 'approved',
            'approved_by_id' => $superAdmin->id,
        ]);

        $response = $this->post("/quotations/{$quote->id}/reopen", [
            'reason' => 'Client requested adjustment to line item 1 quantities',
        ]);
        $response->assertRedirect("/quotations/builder?id={$quote->id}");

        $quote->refresh();
        $this->assertEquals('draft', $quote->status);
        $this->assertNull($quote->approved_by_id);

        $history = $quote->histories()->where('action', 'reopened')->first();
        $this->assertNotNull($history);
        $this->assertStringContainsString('Client requested adjustment', $history->text);
    }

    public function test_reopening_quotation_accepts_and_shares_revised_scope_documents(): void
    {
        Storage::fake('public');
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'draft')->first();
        $quote->update([
            'status' => 'approved',
            'approved_by_id' => $superAdmin->id,
        ]);

        $this->get(route('quotations.builder', ['id' => $quote->id]))
            ->assertOk()
            ->assertSee('Revised scope documents')
            ->assertSeeInOrder(['Reason for edit', 'Revised scope documents', 'Assign to'])
            ->assertSee('name="revised_scope_documents[]"', false)
            ->assertSee('id="revisedScopeDocuments" name="revised_scope_documents[]" type="file" multiple data-plain', false);

        $response = $this->post(route('quotations.reopen', $quote), [
            'reason' => 'Updated drawings and scope',
            'revised_scope_documents' => [
                UploadedFile::fake()->create('revised-scope.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('revised-drawing.dwg', 100, 'application/acad'),
            ],
        ]);

        $response->assertRedirect(route('quotations.builder', ['id' => $quote->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame('draft', $quote->fresh()->status);
        $this->assertDatabaseCount('quotation_attachments', 2);

        foreach (['revised-scope.pdf', 'revised-drawing.dwg'] as $fileName) {
            $attachment = QuotationAttachment::where('quotation_id', $quote->id)
                ->where('file_name', $fileName)
                ->firstOrFail();
            Storage::disk('public')->assertExists($attachment->file_path);
        }

        $this->get(route('quotations.builder', ['id' => $quote->id]))
            ->assertOk()
            ->assertSee('revised-scope.pdf')
            ->assertSee('revised-drawing.dwg');
    }

    public function test_reopening_quotation_rejects_unsupported_scope_documents(): void
    {
        Storage::fake('public');
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'draft')->first();
        $quote->update([
            'status' => 'approved',
            'approved_by_id' => $superAdmin->id,
        ]);

        $this->post(route('quotations.reopen', $quote), [
            'reason' => 'Updated drawings and scope',
            'revised_scope_documents' => [
                UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload'),
            ],
        ])->assertSessionHasErrors(['revised_scope_documents.0']);

        $this->assertSame('approved', $quote->fresh()->status);
        $this->assertDatabaseCount('quotation_attachments', 0);
    }

    public function test_reopen_sent_quotation_increments_revision_number(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $this->actingAs($superAdmin);

        $quote = Quotation::where('status', 'draft')->first();
        $originalNumber = $quote->quote_number;
        $quote->update([
            'status' => 'sent',
        ]);

        $response = $this->post("/quotations/{$quote->id}/reopen", [
            'reason' => 'Revised pricing agreed after client meeting',
        ]);
        $response->assertRedirect("/quotations/builder?id={$quote->id}");

        $quote->refresh();
        $this->assertEquals('draft', $quote->status);
        $this->assertEquals("{$originalNumber}_R1", $quote->quote_number);
    }
}
