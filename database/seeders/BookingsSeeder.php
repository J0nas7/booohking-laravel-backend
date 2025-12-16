<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base data exists
        $users = User::query()->exists()
            ? User::all()
            : User::factory()->count(5)->create();

        $services = Service::query()->exists()
            ? Service::all()
            : Service::factory()->count(5)->create();

        $providers = Provider::query()->exists()
            ? Provider::all()
            : Provider::factory()
            ->count(5)
            ->forService($services->random())
            ->create();

        // Create deterministic, non-overlapping demo bookings
        $startAt = Carbon::tomorrow()->setHour(9)->setMinute(0)->setSecond(0);

        foreach ($providers as $provider) {
            $service = $services->random();

            // 3 bookings per provider, sequential (no overlaps)
            for ($i = 0; $i < 3; $i++) {
                Booking::factory()->create([
                    'User_ID' => $users->random()->User_ID,
                    'Service_ID' => $service->Service_ID,
                    'Provider_ID' => $provider->Provider_ID,
                    'Booking_StartAt' => $startAt,
                    'Booking_EndAt' => (clone $startAt)
                        ->addMinutes($service->Service_DurationMinutes),
                ]);

                // Move to next slot
                $startAt->addMinutes($service->Service_DurationMinutes);
            }
        }
    }
}
