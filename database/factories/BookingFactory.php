<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $durationMinutes = $this->faker->randomElement([30, 45, 60, 90, 120]);

        // Base date
        $base = Carbon::now()
            ->addDays($this->faker->numberBetween(1, 14))
            ->setHour($this->faker->numberBetween(8, 16))
            ->setMinute(0)
            ->setSecond(0);

        // Offset each factory instance by 30-minute slots
        $startAt = (clone $base)->addMinutes(
            $this->faker->unique()->numberBetween(0, 16) * 30
        );

        return [
            // Relationship-driven FKs
            'User_ID' => User::factory(),
            'Service_ID' => Service::factory(),
            'Provider_ID' => Provider::factory(),

            'Booking_StartAt' => $startAt,
            'Booking_EndAt' => (clone $startAt)->addMinutes($durationMinutes),

            'Booking_Status' => 'booked',
        ];
    }

    /**
     * State: cancelled booking
     */
    public function cancelled(): static
    {
        return $this->state(fn() => [
            'Booking_Status' => 'cancelled',
            'Booking_CancelledAt' => now(),
        ]);
    }
}
