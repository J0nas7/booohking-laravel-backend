<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Services\UserServiceTest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyUserTest extends UserServiceTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Test for the 'destroyUser' method to delete a user by ID
    public function testDestroyUser()
    {
        $user = User::factory()->create();

        $response = $this->userService->destroyUser($user->id);
        $this->assertEquals('User deleted', $response->message);
        $this->assertEquals($user->id, $response->data->id);
    }

    public function testDestroyUserThrowsExceptionWhenUserNotFound()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->userService->destroyUser(999999); // non-existing ID
    }

    public function testDestroyUserActuallyDeletesUserFromDatabase()
    {
        $user = User::factory()->create();

        $this->userService->destroyUser($user->id);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function testDestroyUserCannotBeCalledTwiceOnSameUser()
    {
        $user = User::factory()->create();

        // First delete succeeds
        $this->userService->destroyUser($user->id);

        // Second delete should fail
        $this->expectException(ModelNotFoundException::class);

        $this->userService->destroyUser($user->id);
    }
}
