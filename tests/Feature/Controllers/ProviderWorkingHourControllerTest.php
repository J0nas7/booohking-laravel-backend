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
        $this->user = User::factory()->create(['User_Role' => 'ROLE_USER']);
        $this->admin = User::factory()->create(['User_Role' => 'ROLE_ADMIN']);
    }

    protected function authHeaders($user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

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

        $this->assertDatabaseHas('Boo_ProviderWorkingHours', [
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

        $this->assertDatabaseHas('Boo_ProviderWorkingHours', [
            'PWH_ID'      => $pwh->PWH_ID,
            'PWH_DayOfWeek' => 2,
        ]);
    }

    #[Test]
    public function admin_can_delete_working_hour()
    {
        $pwh = ProviderWorkingHour::factory()->create();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->deleteJson("/api/provider-working-hours/{$pwh->PWH_ID}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Deleted successfully']);

        $this->assertSoftDeleted('Boo_ProviderWorkingHours', [
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
}
