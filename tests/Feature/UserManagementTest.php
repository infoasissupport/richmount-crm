<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_view_users_list(): void
    {
        $admin = User::where('email', 'john.doe@richmount.ae')->first();
        $this->actingAs($admin);

        $response = $this->get('/users');
        $response->assertStatus(200);
        $response->assertSee('Users &amp; Roles', false);
        $response->assertSee('Aisha Rahman');
    }

    public function test_super_admin_can_create_user(): void
    {
        $admin = User::where('email', 'john.doe@richmount.ae')->first();
        $branch = Branch::first();
        $this->actingAs($admin);

        $response = $this->post('/users', [
            'name' => 'Kareem Mostafa',
            'email' => 'kareem@richmount.ae',
            'password' => 'secret123',
            'role_name' => 'engineer',
            'branch_id' => $branch->id,
            'phone' => '+971 50 123 9988',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'kareem@richmount.ae']);

        $user = User::where('email', 'kareem@richmount.ae')->first();
        $this->assertTrue($user->hasRole('engineer'));
    }

    public function test_engineer_cannot_create_user(): void
    {
        $engineer = User::where('email', 'ali.khan@richmount.ae')->first();
        $this->actingAs($engineer);

        $response = $this->post('/users', [
            'name' => 'Unauthorized User',
            'email' => 'unauth@richmount.ae',
            'password' => 'secret123',
            'role_name' => 'engineer',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'unauth@richmount.ae']);
    }

    public function test_super_admin_can_update_user(): void
    {
        $admin = User::where('email', 'john.doe@richmount.ae')->first();
        $ali = User::where('email', 'ali.khan@richmount.ae')->first();
        $this->actingAs($admin);

        $response = $this->put("/users/{$ali->id}", [
            'name' => 'Ali Khan Updated',
            'email' => 'ali.khan@richmount.ae',
            'role_name' => 'engineer',
            'phone' => '+971 50 999 8877',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $ali->id,
            'name' => 'Ali Khan Updated',
            'phone' => '+971 50 999 8877',
        ]);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $admin = User::where('email', 'john.doe@richmount.ae')->first();
        $ramesh = User::where('email', 'ramesh.nair@richmount.ae')->first();
        $this->actingAs($admin);

        $response = $this->delete("/users/{$ramesh->id}");
        $response->assertRedirect('/users');

        $this->assertSoftDeleted('users', ['id' => $ramesh->id]);
    }
}
