<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign in');
        $response->assertSee('RICH MOUNT');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'john.doe@richmount.ae',
            'password' => 'demo1234',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'john.doe@richmount.ae',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_switch_demo_role(): void
    {
        $john = User::where('email', 'john.doe@richmount.ae')->first();
        $aisha = User::where('email', 'aisha.rahman@richmount.ae')->first();

        $this->actingAs($john);

        $response = $this->post('/switch-user', [
            'user_id' => $aisha->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($aisha->id, auth()->id());
    }

    public function test_authenticated_user_can_logout(): void
    {
        $john = User::where('email', 'john.doe@richmount.ae')->first();
        $this->actingAs($john);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guest_can_view_registration_page(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Sign up');
        $response->assertSee('Super Admin');
    }

    public function test_guest_can_register_as_super_admin_only(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sara Al Nuaimi',
            'email' => 'sara@richmount.ae',
            'phone' => '+971 50 999 8877',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role_name' => 'engineer', // Even if submitted, role is locked to super_admin
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('email', 'sara@richmount.ae')->first();
        $this->assertNotNull($user);
        $this->assertEquals('super_admin', $user->role_name);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->all_branches);
    }

    public function test_registration_requires_valid_data(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }
}
