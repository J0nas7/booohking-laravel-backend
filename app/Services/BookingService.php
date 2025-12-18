<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\Booking;
use App\Models\ProviderWorkingHour;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class BookingService
{
    // Generate available slots for a provider and service.
    /**
     * @param Provider $provider
     * @param int $days Number of days ahead to generate slots (default 30)
     * @param int $slotDurationMinutes Duration of each slot in minutes (default 30)
     * @param int|null $serviceId Optional service ID to adjust slot duration
     * @return array
     */
    public static function generateAvailableSlots(Provider $provider, int $daysAhead = 30, int $slotMinutes = 30, ?int $serviceId = null): array
    {
        $duration = $serviceId
            ? Service::findOrFail($serviceId)->Service_DurationMinutes
            : $slotMinutes;

        $slots = [];
        $providerTz = $provider->Provider_Timezone ?? 'UTC'; // fallback to UTC

        for ($d = 0; $d < $daysAhead; $d++) {
            $dateLocal = Carbon::today($providerTz)->addDays($d);
            $dayOfWeek = $dateLocal->dayOfWeek;

            $workingHours = ProviderWorkingHour::where('Provider_ID', $provider->Provider_ID)
                ->where('PWH_DayOfWeek', $dayOfWeek)
                ->orderBy('PWH_StartTime')
                ->get();

            if ($workingHours->isEmpty()) {
                continue;
            }

            foreach ($workingHours as $period) {

                // 1) Create provider local start/end
                $startString = $dateLocal->format('Y-m-d') . ' ' . $period->PWH_StartTime;
                $startLocal = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $startString,
                    $providerTz
                );
                $endLocal = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $dateLocal->format('Y-m-d') . ' ' . $period->PWH_EndTime,
                    $providerTz
                );

                // 2) Convert working hours to UTC for slot generation
                $startUTC = $startLocal->copy()->setTimezone('UTC');
                $endUTC   = $endLocal->copy()->setTimezone('UTC');

                // 3) Build the slot period in UTC
                $periodSlots = CarbonPeriod::create($startUTC, "$duration minutes", $endUTC->copy()->subSecond());

                foreach ($periodSlots as $slotStartUTC) {
                    $slotStartUTC = $slotStartUTC->copy();
                    $slotEndUTC   = $slotStartUTC->copy()->addMinutes($duration);

                    // Skip if slot passes working period
                    if ($slotEndUTC->gt($endUTC)) {
                        continue;
                    }

                    // Skip past-time slots for today
                    $nowUTC = Carbon::now('UTC');
                    if ($dateLocal->isToday() && $slotStartUTC->lte($nowUTC)) {
                        continue;
                    }

                    // 4) Overlap detection in UTC
                    $overlap = Booking::where('Provider_ID', $provider->Provider_ID)
                        ->whereDate('Booking_StartAt', $slotStartUTC->toDateString())
                        ->where('Booking_Status', 'booked')
                        ->whereNull('Booking_CancelledAt')
                        ->where(function ($query) use ($slotStartUTC, $slotEndUTC) {
                            $query->whereBetween('Booking_StartAt', [$slotStartUTC, $slotEndUTC->copy()->subSecond()])
                                ->orWhereBetween('Booking_EndAt', [$slotStartUTC->copy()->addSecond(), $slotEndUTC])
                                ->orWhere(function ($q) use ($slotStartUTC, $slotEndUTC) {
                                    $q->where('Booking_StartAt', '<', $slotStartUTC)
                                        ->where('Booking_EndAt', '>', $slotEndUTC);
                                });
                        })
                        ->exists();

                    if ($overlap) {
                        continue;
                    }

                    // 5) Convert slot to provider local timezone for frontend
                    $slotStartLocal = $slotStartUTC->copy()->setTimezone($providerTz);
                    $slotEndLocal   = $slotEndUTC->copy()->setTimezone($providerTz);

                    $slots[] = [
                        'slotStart' => $slotStartLocal,
                        'date'      => $slotStartLocal->format('Y-m-d'),
                        'start'     => $slotStartLocal->format('H:i'),
                        'end'       => $slotEndLocal->format('H:i'),
                    ];
                }
            }
        }

        return $slots;
    }
}
