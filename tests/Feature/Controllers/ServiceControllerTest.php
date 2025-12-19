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
        $this->user = User::factory()->create(['role' => 'ROLE_USER']);
        $this->admin = User::factory()->create(['role' => 'ROLE_ADMIN']);
    }

    protected function authHeaders($user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    // ==== readServicesByUserId() ====
    #[Test]
    public function user_can_read_services_by_user_id()
    {
        // Create some services associated with the user
        $serviceA = Service::factory()->create(['User_ID' => $this->user->User_ID]);
        $serviceB = Service::factory()->create(['User_ID' => $this->user->User_ID]);

        // Send GET request to fetch the services
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/services/users/{$this->user->User_ID}");

        // Assert that the status is OK and services are returned
        $response->assertStatus(200)
            ->assertJsonFragment(['Service_ID' => $serviceA->Service_ID])
            ->assertJsonFragment(['Service_ID' => $serviceB->Service_ID])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['Service_ID', 'Service_Name', 'Service_DurationMinutes']
                ],
                'pagination'
            ]);
    }

    #[Test]
    public function it_returns_no_services_when_user_has_none()
    {
        // Send GET request to fetch services for a user with no services
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/services/users/{$this->user->User_ID}");

        // Assert that the status is 404 and appropriate message is returned
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No services found for this user',
                'data' => [],
            ]);
    }

    #[Test]
    public function it_returns_error_for_invalid_user_id()
    {
        // Use an invalid user ID that doesn't exist
        $invalidUserId = 999999;

        // Send GET request to fetch services for an invalid user ID
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/services/users/{$invalidUserId}");

        // Assert that the status is 404 and appropriate message is returned
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'No query results for model [App\\Models\\User] 999999',
            ]);
    }

    #[Test]
    public function it_returns_error_for_invalid_pagination()
    {
        // Create a service associated with the user
        $service = Service::factory()->create(['User_ID' => $this->user->User_ID]);

        // Send GET request with invalid pagination parameters
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/services/users/{$this->user->User_ID}?page=-1&perPage=-5");

        // Assert that the status is OK and services are returned (default page = 1, perPage = 10)
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['Service_ID', 'Service_Name', 'Service_DurationMinutes']
                ],
                'pagination'
            ]);
    }

    #[Test]
    public function it_returns_services_with_default_pagination_when_missing_params()
    {
        // Create services associated with the user
        $serviceA = Service::factory()->create(['User_ID' => $this->user->User_ID]);
        $serviceB = Service::factory()->create(['User_ID' => $this->user->User_ID]);

        // Send GET request with no pagination parameters
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/services/users/{$this->user->User_ID}");

        // Assert that the status is OK and services are returned with default pagination
        $response->assertStatus(200)
            ->assertJsonFragment(['Service_ID' => $serviceA->Service_ID])
            ->assertJsonFragment(['Service_ID' => $serviceB->Service_ID])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['Service_ID', 'Service_Name', 'Service_DurationMinutes']
                ],
                'pagination'
            ]);
    }

    // ==== index() ====
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
    public function it_returns_empty_service_list_when_no_services_exist()
    {
        // Send GET request when no services are present
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson('/api/services');

        // Assert that the status is 404 and the data array is empty
        $response->assertStatus(404)
            ->assertJsonFragment([
                'data' => []
            ]);
    }

    #[Test]
    public function it_handles_invalid_pagination_parameters_gracefully()
    {
        // Create services for testing
        Service::factory()->count(5)->create();

        // Send GET request with invalid pagination parameters (e.g., page = -1, perPage = 0)
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson('/api/services?page=-1&perPage=0');

        // Assert that the response status is OK and default pagination is used
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['Service_ID', 'Service_Name', 'Service_DurationMinutes']
                ],
                'pagination' => [
                    'currentPage',
                    'lastPage',
                    'perPage',
                    'total'
                ]
            ])
            ->assertJsonPath('pagination.perPage', 1);  // Ensure perPage picks the highest number between 0 and 1
    }

    #[Test]
    public function it_uses_default_pagination_when_no_params_are_provided()
    {
        // Create services for testing
        Service::factory()->count(5)->create();

        // Send GET request without pagination parameters
        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson('/api/services');

        // Assert that the response status is OK and default pagination is applied
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['Service_ID', 'Service_Name', 'Service_DurationMinutes']
                ],
                'pagination' => [
                    'currentPage',
                    'lastPage',
                    'perPage',
                    'total'
                ]
            ])
            ->assertJsonPath('pagination.perPage', 10); // Ensure perPage defaults to 10
    }

    // ==== show() ====
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
    public function it_returns_404_when_service_does_not_exist()
    {
        // Using a random ID that does not exist in the database
        $nonExistentServiceId = 999999;

        $response = $this->withHeaders($this->authHeaders($this->user))
            ->getJson("/api/services/{$nonExistentServiceId}");

        // Assert that the response status is 404 and it contains an error message
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'No query results for model [App\\Models\\Service] 999999',
            ]);
    }

    // ==== store() ====
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

        $this->assertDatabaseHas('services', [
            'Service_Name' => 'Boat Cleaning',
            'Service_DurationMinutes' => 45,
        ]);
    }

    #[Test]
    public function it_returns_422_for_invalid_service_data()
    {
        // Creating service with missing 'Service_Name' and invalid 'Service_DurationMinutes'
        $data = [
            'Service_DurationMinutes' => 'invalid', // Invalid data type (should be integer)
            'Service_Description' => 'A description of the service',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson('/api/services', $data);

        $response->assertStatus(422)  // Unprocessable Entity
            ->assertJsonValidationErrors([
                'Service_Name',  // Missing required field
                'Service_DurationMinutes',  // Invalid data type
            ]);
    }

    // ==== update() ====
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

        $this->assertDatabaseHas('services', [
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
    public function it_returns_404_for_updating_non_existent_service()
    {
        // Assume that a service with this ID does not exist
        $nonExistentServiceId = 99999;

        $payload = [
            'Service_Name' => 'Updated Name',
            'Service_DurationMinutes' => 45,
            'Service_Description' => 'Updated description for non-existent service',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->putJson("/api/services/{$nonExistentServiceId}", $payload);

        // Assert that the response status is 404 (Not Found)
        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'No query results for model [App\\Models\\Service] 99999'
            ]);
    }

    #[Test]
    public function it_returns_422_for_invalid_update_data()
    {
        $service = Service::factory()->create();

        // Invalid data (missing 'Service_Name' and providing a string for 'Service_DurationMinutes')
        $payload = [
            'Service_DurationMinutes' => 'invalid', // Invalid data type (should be integer)
            'Service_Description' => 'Updated description with missing name',
        ];

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->putJson("/api/services/{$service->Service_ID}", $payload);

        $response->assertStatus(422)  // Unprocessable Entity
            ->assertJsonValidationErrors([
                'Service_Name',  // Missing required field
                'Service_DurationMinutes',  // Invalid data type
            ]);
    }

    // ==== destroy() ====
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

        $this->assertSoftDeleted('services', [
            'Service_ID' => $service->Service_ID,
        ]);
    }
}
