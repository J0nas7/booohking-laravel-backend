<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Regular user and admin user
        $this->user = User::factory()->create(['User_Role' => 'ROLE_USER']);
        $this->admin = User::factory()->create(['User_Role' => 'ROLE_ADMIN']);
    }

    protected function authHeaders($user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    #[Test]
    public function user_can_list_services()
    {
        Service::factory()->count(3)->create();

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson('/api/services');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['Service_ID', 'Service_Name', 'Service_DurationMinutes']
                ]
            ]);
    }

    #[Test]
    public function user_can_view_single_service()
    {
        $service = Service::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/services/{$service->Service_ID}");

        $response->assertStatus(200)
            ->assertJsonFragment(['Service_ID' => $service->Service_ID]);
    }

    #[Test]
    public function non_admin_cannot_create_service()
    {
        $data = [
            'Service_Name' => 'Test',
            'Service_DurationMinutes' => 30,
            'Service_Description' => 'Description',
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->postJson('/api/services', $data);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_create_service()
    {
        User::factory()->create();
        $data = [
            'Service_Name' => 'Boat Cleaning',
            'User_ID' => 1,
            'Service_DurationMinutes' => 45,
            'Service_Description' => 'Full cleaning service',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson('/api/services', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['Service_Name' => 'Boat Cleaning']);

        $this->assertDatabaseHas('Boo_Services', [
            'Service_Name' => 'Boat Cleaning',
            'Service_DurationMinutes' => 45,
        ]);
    }

    #[Test]
    public function admin_can_update_service()
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $payload = [
            'Service_Name' => 'Updated Name',
            'User_ID' => $user->User_ID,
            'Service_DurationMinutes' => 60,
            'Service_Description' => 'Updated description',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->putJson("/api/services/{$service->Service_ID}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['Service_Name' => 'Updated Name']);

        $this->assertDatabaseHas('Boo_Services', [
            'Service_ID' => $service->Service_ID,
            'Service_Name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function non_admin_cannot_update_service()
    {
        $service = Service::factory()->create();

        $payload = [
            'Service_Name' => 'Nope',
            'Service_DurationMinutes' => 10,
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->putJson("/api/services/{ $service->Service_ID }");

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_cannot_delete_service_with_bookings()
    {
        $service = Service::factory()->create();
        $provider = Provider::factory()->create();
        Booking::factory()->create(['Service_ID' => $service->Service_ID, 'Provider_ID' => $provider->Provider_ID]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson("/api/services/{$service->Service_ID}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot delete service with existing bookings']);
    }

    #[Test]
    public function admin_can_delete_service_without_bookings()
    {
        $service = Service::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson("/api/services/{$service->Service_ID}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);

        $this->assertSoftDeleted('Boo_Services', [
            'Service_ID' => $service->Service_ID,
        ]);
    }
}
