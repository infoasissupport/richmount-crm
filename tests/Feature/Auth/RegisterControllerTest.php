<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_without_existing_branches(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Sara Al Nuaimi',
            'email' => 'sara@richmount.ae',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'sara@richmount.ae',
            'role_name' => 'super_admin',
            'branch_id' => null,
        ]);
    }
}
