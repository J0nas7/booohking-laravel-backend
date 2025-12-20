<?php

namespace Tests\Unit\Services\AuthServiceTest;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Services\UserServiceTest;
use Illuminate\Database\Eloquent\Collection;

class IndexUsersTest extends UserServiceTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Test for the 'indexUsers' method to list all users (admin only)
    public function testIndexUsers()
    {
        User::factory()->count(3)->create();

        $response = $this->userService->indexUsers();

        $this->assertEquals(3, count($response->data));
        $this->assertEquals('Users listing', $response->message);
    }

    public function testIndexUsersReturnsEmptyCollectionWhenNoUsersExist()
    {
        $response = $this->userService->indexUsers();

        $this->assertCount(0, $response->data);
        $this->assertEquals('Users listing', $response->message);
    }

    public function testIndexUsersReturnsEloquentCollection()
    {
        User::factory()->count(2)->create();

        $response = $this->userService->indexUsers();

        $this->assertInstanceOf(Collection::class, $response->data);
    }

    public function testIndexUsersReturnsCorrectServiceResponseMeta()
    {
        User::factory()->create();

        $response = $this->userService->indexUsers();

        $this->assertEquals(200, $response->status);
        $this->assertEquals('Users listing', $response->message);
    }

    public function testIndexUsersReturnsAllUsers()
    {
        $users = User::factory()->count(5)->create();

        $response = $this->userService->indexUsers();

        $this->assertCount(5, $response->data);
        $this->assertEquals(
            $users->pluck('id')->sort()->values()->all(),
            $response->data->pluck('id')->sort()->values()->all()
        );
    }
}
