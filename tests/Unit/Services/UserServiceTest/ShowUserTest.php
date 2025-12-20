<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Services\UserServiceTest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowUserTest extends UserServiceTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Test for the 'showUser' method to get a specific user by ID
    public function testShowUser()
    {
        $user = User::factory()->create();

        $response = $this->userService->showUser($user->id);
        $this->assertEquals($user->id, $response->data->id);
        $this->assertEquals('User found', $response->message);
    }

    public function testShowUserThrowsExceptionWhenUserNotFound()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->userService->showUser(999999);
    }

    public function testShowUserReturnsCorrectUserData()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $response = $this->userService->showUser($user->id);

        $this->assertEquals('Jane Doe', $response->data->name);
        $this->assertEquals('jane@example.com', $response->data->email);
    }

    public function testShowUserReturnsCorrectServiceResponseMeta()
    {
        $user = User::factory()->create();

        $response = $this->userService->showUser($user->id);

        $this->assertEquals(200, $response->status);
        $this->assertEquals('User found', $response->message);
    }
}
