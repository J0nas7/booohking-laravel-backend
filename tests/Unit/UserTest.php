<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching all users.
     */
    public function test_get_all_users()
    {
        User::factory()->count(5)->create();
        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    /**
     * Test fetching a user by ID.
     */
    public function test_admin_get_user_by_id()
    {
        $user = User::factory()->create(['User_Role' => 'ROLE_ADMIN']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/users/' . $user->User_ID);

        $response->assertStatus(200)
            ->assertJsonFragment(['User_ID' => $user->User_ID]);
    }

    /**
     * Test fetching a user that does not exist.
     */
    public function test_get_user_by_invalid_id()
    {
        $user = User::factory()->create(['User_Role' => 'ROLE_ADMIN']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/users/99999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'No query results for model [App\\Models\\User] 99999']);
    }

    /**
     * Test creating a new user.
     */
    public function test_create_user_success()
    {
        $userData = [
            'email'            => 'test@example.com',
            'User_Email'            => 'test@example.com',
            'User_Name'             => 'Test User',
            'User_Password'         => 'password',
            'User_Password_confirmation' => 'password',
            'User_Remember_Token'   => Str::random(10),
            'User_CreatedAt'        => now(),
            'User_UpdatedAt'        => now(),
            'User_DeletedAt'        => null, // Optional
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonFragment(['User_Email' => 'test@example.com']);
    }

    /**
     * Test creating a user with invalid data.
     */
    public function test_create_user_with_invalid_data()
    {
        $userData = [
            'User_Email' => 'invalid_email',
            'User_Password' => 'short',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /**
     * Test updating an existing user.
     */
    public function test_update_user_success()
    {
        $user = User::factory()->create();
        $user->User_Email = 'updated@example.com';

        $response = $this->putJson('/api/users/' . $user->User_ID, $user->toArray());

        $response->assertStatus(200)
            ->assertJsonFragment(['User_Email' => 'updated@example.com']);
    }

    /**
     * Test updating a non-existent user.
     */
    public function test_update_nonexistent_user()
    {
        $updateData = [
            'User_Email' => 'nonexistent@example.com',
        ];

        $response = $this->putJson('/api/users/99999', $updateData);

        $response->assertStatus(404)
            ->assertJson(['message' => 'No query results for model [App\\Models\\User] 99999']);
    }

    /**
     * Test deleting a user.
     */
    public function test_delete_user_success()
    {
        $user = User::factory()->create();
        $response = $this->deleteJson('/api/users/' . $user->User_ID);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);
        $this->assertSoftDeleted($user);
    }

    /**
     * Test deleting a non-existent user.
     */
    public function test_delete_nonexistent_user()
    {
        $response = $this->deleteJson('/api/users/99999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'No query results for model [App\\Models\\User] 99999']);
    }

    /**
     * Edge Case: Attempt to create a user with duplicate email.
     */
    public function test_create_user_duplicate_email()
    {
        User::factory()->create(['User_Email' => 'test@example.com']);
        $userData = [
            'User_Email' => 'test@example.com',
            'User_Password' => 'password123',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /**
     * Edge Case: Update user with invalid email format.
     */
    public function test_update_user_invalid_email()
    {
        $user = User::factory()->create();
        $updateData = [
            'User_Email' => 'not-an-email',
        ];

        $response = $this->putJson('/api/users/' . $user->User_ID, $updateData);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /**
     * Edge Case: Attempt to delete an already deleted user.
     */
    public function test_delete_already_deleted_user()
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->deleteJson('/api/users/' . $user->User_ID);

        $response->assertStatus(404)
            ->assertJson(['message' => 'No query results for model [App\\Models\\User] 1']);
    }
}
