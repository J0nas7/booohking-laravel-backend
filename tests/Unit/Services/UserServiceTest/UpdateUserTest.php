<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Services\UserServiceTest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

class UpdateUserTest extends UserServiceTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Test for the 'updateUser' method to update a user's information
    public function testUpdateUser()
    {
        $user = User::factory()->create();
        $updatedData = [
            'name' => 'John Doe Updated',
            'email' => 'john.doe.updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->userService->updateUser($updatedData, $user->id);
        $this->assertEquals('User updated', $response->message);
        $this->assertEquals($user->id, $response->data->id);
    }

    public function testUpdateUserThrowsExceptionWhenUserNotFound()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->userService->updateUser([
            'name' => 'No One',
        ], 999999);
    }

    public function testUpdateUserWithoutPasswordDoesNotChangePassword()
    {
        $user = User::factory()->create([
            'password' => bcrypt('original-password'),
        ]);

        $response = $this->userService->updateUser([
            'name' => 'Name Only Update',
        ], $user->id);

        $user->refresh();

        $this->assertEquals('Name Only Update', $user->name);
        $this->assertTrue(
            Hash::check('original-password', $user->password)
        );
    }

    public function testUpdateUserHashesPasswordWhenProvided()
    {
        $user = User::factory()->create();

        $this->userService->updateUser([
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ], $user->id);

        $user->refresh();

        $this->assertTrue(
            Hash::check('new-secret-password', $user->password)
        );
    }

    public function testUpdateUserWithPartialData()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $this->userService->updateUser([
            'email' => 'updated@example.com',
        ], $user->id);

        $user->refresh();

        $this->assertEquals('Original Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }
}
