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
        $this->admin = User::factory()->create(['role' => 'ROLE_ADMIN']);
        $this->user = User::factory()->create(['role' => 'ROLE_USER']);
    }

    protected function authHeaders(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    // ---- index() ----
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
    public function it_returns_empty_data_when_page_exceeds_total()
    {
        Provider::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/providers?page=100&perPage=5');

        $response->assertStatus(200)
            ->assertJson([
                'current_page' => 100,
                'data' => [],
            ]);
    }

    #[Test]
    public function it_falls_back_to_default_per_page_when_invalid()
    {
        Provider::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/providers?page=1&perPage=-5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'per_page',
                'total',
            ]);

        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    // ---- show() ----
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
    public function it_returns_404_for_non_existent_provider()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/providers/999999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_cached_provider_if_exists()
    {
        $provider = Provider::factory()->create();
        \Illuminate\Support\Facades\Cache::put("model:provider:{$provider->Provider_ID}", $provider->toJson(), 3600);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/providers/{$provider->Provider_ID}");

        $response->assertStatus(200)
            ->assertJson(['Provider_ID' => $provider->Provider_ID]);
    }


    // ---- store() ----
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

        $this->assertDatabaseHas('providers', ['Provider_Name' => 'Test Provider']);
    }

    #[Test]
    public function store_requires_provider_name_and_service_id()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/providers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['Provider_Name', 'Service_ID']);
    }

    #[Test]
    public function store_fails_if_service_id_does_not_exist()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/providers', ['Provider_Name' => 'Test', 'Service_ID' => 999]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['Service_ID']);
    }

    // ---- update() ----
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

        $this->assertDatabaseHas('providers', ['Provider_Name' => 'Updated Name']);
    }

    #[Test]
    public function update_returns_404_for_non_existent_provider()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->putJson('/api/providers/999999', ['Provider_Name' => 'Test', 'Service_ID' => 1]);

        $response->assertStatus(404);
    }

    #[Test]
    public function update_fails_with_invalid_service_id()
    {
        $provider = Provider::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/providers/{$provider->Provider_ID}", ['Provider_Name' => 'Test', 'Service_ID' => 999]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['Service_ID']);
    }

    // ---- destroy() ----
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

        $this->assertSoftDeleted('providers', ['Provider_ID' => $provider->Provider_ID]);
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

    #[Test]
    public function destroy_returns_404_for_non_existent_provider()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/providers/999999');

        $response->assertStatus(404);
    }

    #[Test]
    public function destroy_soft_deletes_multiple_providers()
    {
        $providers = Provider::factory()->count(3)->create();

        foreach ($providers as $provider) {
            $response = $this->actingAs($this->admin, 'api')
                ->deleteJson("/api/providers/{$provider->Provider_ID}");

            $response->assertStatus(200)
                ->assertJson(['message' => 'Deleted successfully']);

            $this->assertSoftDeleted('providers', ['Provider_ID' => $provider->Provider_ID]);
        }
    }

    // ---- readProvidersByServiceID() ----
    #[Test]
    public function it_reads_providers_by_service_id()
    {
        $service = Service::factory()->create();
        $providers = Provider::factory()->count(3)->create(['Service_ID' => $service->Service_ID]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/providers/services/{$service->Service_ID}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Providers found'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'Provider_ID',
                        'Provider_Name',
                        'Service_ID',
                    ]
                ],
                'pagination' => ['total', 'perPage', 'currentPage', 'lastPage']
            ]);
    }

    #[Test]
    public function it_returns_404_when_no_providers_for_service()
    {
        $service = Service::factory()->create(); // no providers created

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/providers/services/{$service->Service_ID}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No providers found for this service',
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'perPage' => 10,
                    'currentPage' => 1,
                    'lastPage' => 0,
                ]
            ]);
    }

    #[Test]
    public function it_returns_404_when_requesting_page_beyond_last()
    {
        $service = Service::factory()->create();
        Provider::factory()->count(3)->create(['Service_ID' => $service->Service_ID]);

        // perPage = 2, lastPage = 2, request page 5
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/providers/services/{$service->Service_ID}?page=5&perPage=2");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No providers found for this service',
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'perPage' => 2,
                    'currentPage' => 5,
                    'lastPage' => 0,
                ]
            ]);
    }
}
