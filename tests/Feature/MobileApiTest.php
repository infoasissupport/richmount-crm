<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_mobile_client_can_login_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ali.khan@richmount.ae',
            'password' => 'demo1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'role_label',
                    'permissions',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_mobile_client_can_access_profile_with_token(): void
    {
        $user = User::where('email', 'ali.khan@richmount.ae')->first();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'ali.khan@richmount.ae')
            ->assertJsonPath('user.role', 'engineer');
    }
}
