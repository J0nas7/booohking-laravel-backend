<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\Booking;
use App\Models\ProviderWorkingHour;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BookingService
{
    /**
     * Generate available slots for a provider and service.
     *
     * @param Provider $provider
     * @param int $days Number of days ahead to generate slots (default 30)
     * @param int $slotDurationMinutes Duration of each slot in minutes (default 30)
     * @param int|null $serviceId Optional service ID to adjust slot duration
     * @return array
     */
    public static function generateAvailableSlots(Provider $provider, int $daysAhead = 30, int $slotMinutes = 30, ?int $serviceId = null): array
    {
        $duration = $slotMinutes;

        if ($serviceId) {
            $service = Service::findOrFail($serviceId);
            $duration = $service->Service_DurationMinutes;
        }

        $slots = [];

        for ($d = 0; $d < $daysAhead; $d++) {
            $date = Carbon::today()->addDays($d);
            $dayOfWeek = $date->dayOfWeek;

            $workingHours = ProviderWorkingHour::where('Provider_ID', $provider->Provider_ID)
                ->where('PWH_DayOfWeek', $dayOfWeek)
                ->orderBy('PWH_DayOfWeek')  // ensure chronological order
                ->get();

            if (!$workingHours) {
                continue;
            }

            foreach ($workingHours as $period) {
                $startTime = substr($period->PWH_StartTime, 0, 5); // "HH:MM"
                $endTime = substr($period->PWH_EndTime, 0, 5);

                $start = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $startTime);
                $end   = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $endTime);

                $periodSlots = CarbonPeriod::create($start, "$duration minutes", $end->copy()->subSecond());

                foreach ($periodSlots as $slotStart) {
                    $slotStart = $slotStart->copy(); // clone to avoid mutation
                    $slotEnd = $slotStart->copy()->addMinutes($duration);

                    // Skip slots that go past the working period
                    if ($slotEnd->gt($end)) {
                        continue;
                    }

                    // skip past slots for today
                    if ($date->isToday() && $slotStart->lte(Carbon::now())) {
                        continue;
                    }

                    // Skip if slot is already booked
                    $overlap = Booking::where('Provider_ID', $provider->Provider_ID)
                        ->whereDate('Booking_StartAt', $date->toDateString())
                        ->where('Booking_Status', 'booked') // Ensure only "booked" status is checked
                        ->whereNull('Booking_CancelledAt') // Ensure the "CancelledAt" field is null
                        ->where(function ($query) use ($slotStart, $slotEnd) {
                            $query->whereBetween('Booking_StartAt', [$slotStart, $slotEnd->copy()->subSecond()])
                                ->orWhereBetween('Booking_EndAt', [$slotStart->copy()->addSecond(), $slotEnd])
                                ->orWhere(function ($q) use ($slotStart, $slotEnd) {
                                    $q->where('Booking_StartAt', '<', $slotStart)
                                        ->where('Booking_EndAt', '>', $slotEnd);
                                });
                        })
                        ->exists();

                    if ($overlap) {
                        continue;
                    }

                    $slots[] = [
                        'date'  => $slotStart->format('Y-m-d'), // use slotStart date
                        'start' => $slotStart->format('H:i'),
                        'end'   => $slotEnd->format('H:i'),
                    ];
                }
            }
        }

        return $slots;
    }
}
