<?php

namespace Tests\Feature\Controllers;

use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class ProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin and a regular user
        $this->admin = User::factory()->create(['User_Role' => 'ROLE_ADMIN']);
        $this->user = User::factory()->create(['User_Role' => 'ROLE_USER']);
    }

    protected function authHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    #[Test]
    public function it_lists_providers_with_pagination()
    {
        Provider::factory()->count(15)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/providers?page=2&perPage=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'per_page',
                'total',
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function it_shows_a_single_provider()
    {
        $provider = Provider::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/providers/{$provider->Provider_ID}");

        $response->assertStatus(200)
            ->assertJson([
                'Provider_ID' => $provider->Provider_ID,
                'Provider_Name' => $provider->Provider_Name
            ]);
    }

    #[Test]
    public function only_admin_can_create_provider()
    {
        Service::factory()->create();
        $data = [
            'Provider_Name' => 'Test Provider',
            'Service_ID' => 1
        ];

        // Regular user cannot create
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/providers', $data);
        $response->assertStatus(403);

        // Admin can create
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/providers', $data);
        $response->assertStatus(201)
            ->assertJson(['Provider_Name' => 'Test Provider']);

        $this->assertDatabaseHas('Boo_Providers', ['Provider_Name' => 'Test Provider']);
    }

    #[Test]
    public function only_admin_can_update_provider()
    {
        $provider = Provider::factory()->create();

        $data = [
            'Provider_Name' => 'Updated Name',
            'Service_ID' => 1
        ];

        // Regular user cannot update
        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/providers/{$provider->Provider_ID}", $data);
        $response->assertStatus(403);

        // Admin can update
        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/providers/{$provider->Provider_ID}", $data);
        $response->assertStatus(200)
            ->assertJson(['Provider_Name' => 'Updated Name']);

        $this->assertDatabaseHas('Boo_Providers', ['Provider_Name' => 'Updated Name']);
    }

    #[Test]
    public function only_admin_can_delete_provider()
    {
        $provider = Provider::factory()->create();

        // Regular user cannot delete
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/providers/{$provider->Provider_ID}");
        $response->assertStatus(403);

        // Admin can delete
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/providers/{$provider->Provider_ID}");
        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);

        $this->assertSoftDeleted('Boo_Providers', ['Provider_ID' => $provider->Provider_ID]);
    }

    #[Test]
    public function cannot_delete_provider_with_existing_bookings()
    {
        $provider = Provider::factory()->create();
        // Create a booking for this provider
        \App\Models\Booking::factory()->create(['Provider_ID' => $provider->Provider_ID]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson("/api/providers/{$provider->Provider_ID}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot delete provider with existing bookings']);
    }
}
