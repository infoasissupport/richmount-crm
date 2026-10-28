<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Authenticated user sees the dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $this->seed();
        $user = User::first();

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }
}
