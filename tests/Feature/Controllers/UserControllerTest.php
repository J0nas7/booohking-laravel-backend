<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Make admin
        $this->admin = User::factory()->create([
            'User_Email' => 'admin@example.com',
            'role' => 'ROLE_ADMIN'
        ]);

        // Make normal user
        $this->user = User::factory()->create([
            'User_Email' => 'user@example.com',
            'role' => 'ROLE_USER'
        ]);
    }

    protected function authHeaders($user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    // ==== index() ====
    #[Test]
    public function can_list_users()
    {
        User::factory()->count(3)->create();

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['User_ID', 'name', 'User_Email']
            ]);
    }

    // ==== store() ====
    #[Test]
    public function can_create_user()
    {
        $payload = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'User_Email' => 'newuser@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'ROLE_USER',
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->postJson('/api/users', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['User_Email' => 'newuser@example.com']);

        $this->assertDatabaseHas('users', [
            'User_Email' => 'newuser@example.com',
        ]);

        $created = User::where('User_Email', 'newuser@example.com')->first();
        $this->assertTrue(Hash::check('secret123', $created->password));
    }

    #[Test]
    public function email_must_be_unique()
    {
        User::factory()->create([
            'User_Email' => 'duplicate@example.com'
        ]);

        $payload = [
            'name' => 'User',
            'User_Email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson('/api/users', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['User_Email']);
    }

    // ==== show() ====
    #[Test]
    public function admin_can_view_single_user()
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson("/api/users/{$user->User_ID}");

        $response->assertStatus(200)
            ->assertJsonFragment(['User_ID' => $user->User_ID]);
    }

    #[Test]
    public function non_admin_cannot_view_single_user()
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/users/{$user->User_ID}");

        $response->assertStatus(403);
    }

    // ==== update() ====
    #[Test]
    public function can_update_user()
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Updated Name',
            'User_Email' => 'updated@example.com',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
            'role' => 'ROLE_ADMIN',
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->putJson("/api/users/{$user->User_ID}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['User_Email' => 'updated@example.com']);

        $this->assertDatabaseHas('users', [
            'User_ID' => $user->User_ID,
            'User_Email' => 'updated@example.com',
        ]);
    }

    // ==== destroy() ====
    #[Test]
    public function can_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->deleteJson("/api/users/{$user->User_ID}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);

        $this->assertSoftDeleted('users', [
            'User_ID' => $user->User_ID,
        ]);
    }
}
