<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_view_branches(): void
    {
        $admin = User::where('email', 'john.doe@richmount.ae')->first();
        $this->actingAs($admin);

        $response = $this->get('/branches');
        $response->assertStatus(200);
        $response->assertSee('Dubai Branch');
        $response->assertSee('Abu Dhabi Branch');
        $response->assertSee('Sharjah Branch');
    }

    public function test_super_admin_can_create_branch(): void
    {
        $admin = User::where('email', 'john.doe@richmount.ae')->first();
        $this->actingAs($admin);

        $response = $this->post('/branches', [
            'name' => 'Ras Al Khaimah Branch',
            'short_name' => 'RAK',
            'code' => 'Branch 4',
            'country' => 'UAE',
            'quote_format' => 'format_1',
            'trn_number' => '100 234 567 800 004',
            'phone' => '+971 7 123 4567',
        ]);

        $response->assertRedirect('/branches');
        $this->assertDatabaseHas('branches', ['code' => 'Branch 4']);
    }

    public function test_super_admin_can_update_branch(): void
    {
        $admin = User::where('email', 'john.doe@richmount.ae')->first();
        $branch = Branch::first();
        $this->actingAs($admin);

        $response = $this->put("/branches/{$branch->id}", [
            'name' => 'Dubai Main HQ',
            'short_name' => 'Dubai HQ',
            'code' => $branch->code,
            'country' => 'UAE',
            'quote_format' => 'format_1',
        ]);

        $response->assertRedirect('/branches');
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Dubai Main HQ',
        ]);
    }
}
