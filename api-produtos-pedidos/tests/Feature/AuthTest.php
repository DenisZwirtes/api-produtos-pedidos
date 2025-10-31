<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
	use RefreshDatabase;

	public function test_register_creates_user_and_returns_token(): void
	{
		$response = $this->post('/api/register', [
			'name' => 'John Doe',
			'email' => 'john@example.com',
			'password' => 'password123',
			'password_confirmation' => 'password123',
		]);

		$response->assertCreated()
			->assertJsonStructure(['user' => ['id', 'email'], 'token']);
		$this->assertDatabaseHas('users', ['email' => 'john@example.com']);
	}

	public function test_login_returns_token(): void
	{
		$user = User::factory()->create(['password' => bcrypt('password123')]);
		$response = $this->post('/api/login', [
			'email' => $user->email,
			'password' => 'password123',
		]);
		$response->assertOk()->assertJsonStructure(['user', 'token']);
	}

	public function test_logout_revokes_token(): void
	{
		$user = User::factory()->create(['password' => bcrypt('password123')]);
		$token = $user->createToken('t')->plainTextToken;
		$response = $this->withHeader('Authorization', 'Bearer '.$token)
			->post('/api/logout');
		$response->assertOk();
	}
}
