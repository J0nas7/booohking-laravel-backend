<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Random booking duration between 30 and 120 minutes
        $durationMinutes = $this->faker->randomElement([30, 45, 60, 90, 120]);

        // Generate a random start time
        $startAt = Carbon::now()
            ->addDays($this->faker->numberBetween(1, 10))
            ->setHour($this->faker->numberBetween(8, 16))
            ->setMinute($this->faker->randomElement([0, 30]));

        $endAt = $startAt->copy()->addMinutes($durationMinutes);

        // Pick or create a provider
        $provider = Provider::factory()->create();

        // Ensure uniqueness per provider by adding a small random offset if needed
        $existingBookings = Booking::where('Provider_ID', $provider->id)
            ->pluck('Booking_StartAt')
            ->toArray();

        while (in_array($startAt->toDateTimeString(), $existingBookings)) {
            // Add 30 minutes until unique
            $startAt->addMinutes(30);
            $endAt = $startAt->copy()->addMinutes($durationMinutes);
        }

        return [
            'Provider_ID' => $provider->Provider_ID,
            'User_ID' => User::factory(),
            'Service_ID' => Service::factory(),
            'Booking_StartAt' => $startAt,
            'Booking_EndAt' => $endAt,
        ];
    }
}
