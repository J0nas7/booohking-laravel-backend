<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('User_Email', 'jonas-usr@booohking.com')->first();
        $service = Service::first();
        $provider = Provider::first();

        // Example booking tomorrow at 10:00
        $start = Carbon::tomorrow()->setHour(10)->setMinute(0)->setSecond(0);

        Booking::create([
            'User_ID' => $user->User_ID,
            'Provider_ID' => $provider->Provider_ID,
            'Service_ID' => $service->Service_ID,
            'Booking_StartAt' => $start,
            'Booking_EndAt' => $start->copy()->addMinutes($service->Service_DurationMinutes),
            'Booking_Status' => 'booked',
        ]);
    }
}
