<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Provider;
use App\Models\ProviderWorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class ProviderWorkingHourControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Regular user and admin user
        $this->user = User::factory()->create(['role' => 'ROLE_USER']);
        $this->admin = User::factory()->create(['role' => 'ROLE_ADMIN']);
    }

    protected function authHeaders($user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    // ==== index() ====
    #[Test]
    public function it_lists_working_hours_with_pagination()
    {
        ProviderWorkingHour::factory()->count(12)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/provider-working-hours?page=2&perPage=5');

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
    public function it_filters_working_hours_by_provider()
    {
        $providerA = Provider::factory()->create();
        $providerB = Provider::factory()->create();

        ProviderWorkingHour::factory()->count(3)->create(['Provider_ID' => $providerA->Provider_ID]);
        ProviderWorkingHour::factory()->count(2)->create(['Provider_ID' => $providerB->Provider_ID]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/provider-working-hours?Provider_ID={$providerA->Provider_ID}");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_falls_back_to_default_page_when_invalid()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/provider-working-hours?page=-1&perPage=0');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'per_page',
                'total',
            ]);

        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    #[Test]
    public function it_returns_empty_data_when_no_working_hours_exist()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/provider-working-hours');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    // ==== show() ====
    #[Test]
    public function it_shows_a_single_working_hour()
    {
        // Create a provider and a working hour for that provider
        $provider = Provider::factory()->create();
        $pwh = ProviderWorkingHour::factory()->create(['Provider_ID' => $provider->Provider_ID]);

        // Send a GET request to show the specific working hour by ID
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/provider-working-hours/{$pwh->PWH_ID}");

        // Assert that the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert that the response contains the expected data
        $response->assertJsonFragment([
            'PWH_ID' => $pwh->PWH_ID,
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => $pwh->PWH_DayOfWeek,
        ]);
    }

    #[Test]
    public function it_returns_error_for_non_existing_working_hour()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/provider-working-hours/999999");  // Non-existent ID

        $response->assertStatus(404);  // Not Found
    }

    // ==== store() ====
    #[Test]
    public function admin_can_create_working_hour()
    {
        $provider = Provider::factory()->create();

        $payload = [
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek'   => 1,
            'PWH_StartTime'   => '09:00',
            'PWH_EndTime'     => '17:00',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson('/api/provider-working-hours', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['PWH_DayOfWeek' => 1]);

        $this->assertDatabaseHas('provider_working_hours', [
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => 1,
        ]);
    }

    #[Test]
    public function non_admin_cannot_create_working_hour()
    {
        $provider = Provider::factory()->create();

        $payload = [
            'Provider_ID' => $provider->Provider_ID,
            'DayOfWeek'   => 1,
            'StartTime'   => '09:00',
            'EndTime'     => '17:00',
        ];

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->postJson('/api/provider-working-hours', $payload);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_error_when_required_fields_missing()
    {
        $provider = Provider::factory()->create();

        $payload = [
            'Provider_ID' => $provider->Provider_ID,
            'PWH_DayOfWeek' => 1,
            // Missing StartTime and EndTime
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson('/api/provider-working-hours', $payload);

        $response->assertStatus(422);  // Unprocessable Entity
    }

    #[Test]
    public function it_returns_error_for_invalid_provider_id()
    {
        $payload = [
            'Provider_ID' => 999999,  // Non-existent Provider_ID
            'PWH_DayOfWeek' => 1,
            'PWH_StartTime' => '09:00',
            'PWH_EndTime' => '17:00',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson('/api/provider-working-hours', $payload);

        $response->assertStatus(422);  // Validation error for non-existent provider
    }

    // ==== update() ====
    #[Test]
    public function admin_can_update_working_hour()
    {
        $pwh = ProviderWorkingHour::factory()->create();

        $payload = [
            'Provider_ID' => $pwh->Provider_ID,
            'PWH_DayOfWeek'   => 2,
            'PWH_StartTime'   => '10:00',
            'PWH_EndTime'     => '18:00',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->putJson("/api/provider-working-hours/{$pwh->PWH_ID}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['PWH_DayOfWeek' => 2]);

        $this->assertDatabaseHas('provider_working_hours', [
            'PWH_ID'      => $pwh->PWH_ID,
            'PWH_DayOfWeek' => 2,
        ]);
    }

    #[Test]
    public function it_returns_error_for_non_existing_working_hour_update()
    {
        $payload = [
            'Provider_ID' => 1,
            'PWH_DayOfWeek' => 2,
            'PWH_StartTime' => '10:00',
            'PWH_EndTime' => '18:00',
        ];

        // Attempt to update a non-existent working hour (ID 99999)
        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->putJson('/api/provider-working-hours/99999', $payload);

        $response->assertStatus(404);  // Not Found
    }

    #[Test]
    public function it_returns_error_for_invalid_update_data()
    {
        $pwh = ProviderWorkingHour::factory()->create();

        $payload = [
            'Provider_ID' => $pwh->Provider_ID,
            'PWH_DayOfWeek' => 2,
            'PWH_StartTime' => '10:00',
            'PWH_EndTime' => '09:00',
            // Invalid: End time is before start time
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->putJson("/api/provider-working-hours/{$pwh->PWH_ID}", $payload);

        $response->assertStatus(422);  // Unprocessable Entity (validation error)
    }

    // ==== destroy() ====
    #[Test]
    public function admin_can_delete_working_hour()
    {
        $pwh = ProviderWorkingHour::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson("/api/provider-working-hours/{$pwh->PWH_ID}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);

        $this->assertSoftDeleted('provider_working_hours', [
            'PWH_ID' => $pwh->PWH_ID,
        ]);
    }

    #[Test]
    public function non_admin_cannot_delete_working_hour()
    {
        $pwh = ProviderWorkingHour::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->deleteJson("/api/provider-working-hours/{$pwh->PWH_ID}");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_error_for_non_existing_working_hour_delete()
    {
        // Attempt to delete a non-existent working hour (ID 99999)
        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson('/api/provider-working-hours/99999');

        $response->assertStatus(404);  // Not Found
    }
}
