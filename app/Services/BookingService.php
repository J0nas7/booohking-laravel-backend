<?php

namespace App\Services;

use App\Actions\GenerateAvailableSlots\GenerateAvailableSlots;
use App\Helpers\ServiceResponse;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BookingService
{
    protected string $modelClass = Booking::class;

    protected array $with = ['user', 'provider.service', 'service'];

    /**
     * @param GenerateAvailableSlots $generateAvailableSlots
     * @return void
     */
    public function __construct(
        protected GenerateAvailableSlots $generateAvailableSlots
    ) {}

    // Generate available slots for a provider and service.
    /**
     * This endpoint calculates 30-minute slots (or based on service duration if provided)
     * for the next 30 days, taking into account the provider's working hours
     * and any existing bookings. Only future time slots are returned.
     *
     * @param array $validated
     * @param \App\Models\Provider $provider
     * @return \App\Helpers\ServiceResponse
     */
    public function generateAvailableSlots(array $validated, Provider $provider): ServiceResponse
    {
        // Misc (validated & normalized)
        ['service_id' => $serviceId, 'daysAhead' => $daysAhead, 'slotMinutes' => $slotMinutes] = $validated;

        // Pagination (validated & normalized)
        ['page' => $page, 'perPage' => $perPage] = $validated;

        $result = $this->generateAvailableSlots->execute($provider, $daysAhead, $slotMinutes, $serviceId);

        $total = count($result->data);
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedSlots = array_slice($result->data, $offset, $perPage);

        // Successful response
        return new ServiceResponse(
            data: [
                "data" => $paginatedSlots,
                "total" => $total,
                "pagination" => [
                    "total" => $total,
                    "perPage" => $perPage,
                    "currentPage" => $page,
                    "lastPage" => $lastPage
                ]
            ],
            message: 'Slots found',
        );
    }

    // Retrieve paginated bookings for a specific user.
    /**
     * @param array $validated
     * @param \App\Models\User $user
     * @return \App\Helpers\ServiceResponse
     */
    public function readBookingsByUserID(array $validated, User $user): ServiceResponse
    {
        // Pagination (validated & normalized)
        ['page' => $page, 'perPage' => $perPage] = $validated;

        // Build query: all bookings for this user
        $query = $this->modelClass::with($this->with)
            ->where('User_ID', $user->id)
            ->orderBy('Booking_StartAt', 'desc');

        // Paginate results
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        // Empty case
        if ($paginated->isEmpty()) {
            return new ServiceResponse(
                data: [
                    'data' => [],
                    'pagination' => [
                        'total' => 0,
                        'perPage' => $perPage,
                        'currentPage' => $page,
                        'lastPage' => 0,
                    ]
                ],
                message: 'No bookings found for this user',
                status: 404
            );
        }

        // Successful response
        return new ServiceResponse(
            data: [
                'data' => $paginated->items(),
                'pagination' => [
                    'total' => $paginated->total(),
                    'perPage' => $paginated->perPage(),
                    'currentPage' => $paginated->currentPage(),
                    'lastPage' => $paginated->lastPage(),
                ]
            ],
            message: 'Bookings found',
        );
    }

    // List all bookings for admins, or only user's bookings
    /**
     * @param array $validated
     * @return \App\Helpers\ServiceResponse
     */
    public function index(array $validated): ServiceResponse
    {
        $user = Auth::guard('api')->user();

        // Pagination (validated & normalized)
        ['page' => $page, 'perPage' => $perPage] = $validated;

        $query = ($this->modelClass)::query()->with($this->with);

        // Non-admins see only their own bookings
        if ($user->role !== 'ROLE_ADMIN') {
            $query->where('User_ID', $user->id);
        }

        // Paginate the results
        $paginated = $query->orderBy('Booking_StartAt', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Successful response
        return new ServiceResponse(
            data: [
                'data' => $paginated->items(),
                'pagination' => [
                    'total' => $paginated->total(),
                    'perPage' => $paginated->perPage(),
                    'currentPage' => $paginated->currentPage(),
                    'lastPage' => $paginated->lastPage(),
                ]
            ],
            message: 'Bookings found',
        );
    }

    // Create a new booking (any authenticated user)
    /**
     * @param array $validated
     * @return \App\Helpers\ServiceResponse
     */
    public function store(array $validated): ServiceResponse
    {
        $startAt = $validated['Booking_StartAt'];
        $endAt = $validated['Booking_EndAt'];
        $providerId = $validated['Provider_ID'];

        // Prevent double booking: check overlapping bookings
        $exists = $this->modelClass::where('Provider_ID', $providerId)
            ->where(function ($query) use ($startAt, $endAt) {
                $query->whereBetween('Booking_StartAt', [$startAt, $endAt])
                    ->orWhereBetween('Booking_EndAt', [$startAt, $endAt])
                    ->orWhere(function ($q) use ($startAt, $endAt) {
                        $q->where('Booking_StartAt', '<=', $startAt)
                            ->where('Booking_EndAt', '>=', $endAt);
                    });
            })
            ->exists();

        if ($exists) {
            return new ServiceResponse(
                error: 'This time slot is already booked.',
                status: 422 // Unprocessable Content
            );
        }

        $item = ($this->modelClass)::create($validated);

        return new ServiceResponse(
            data: $item,
            message: 'Booking created successfully',
            status: 201
        );
    }

    // View a single booking
    /**
     * @param int $id
     * @return \App\Helpers\ServiceResponse
     */
    public function show(int $id): ServiceResponse
    {
        $item = ($this->modelClass)::with($this->with)->findOrFail($id);

        $user = Auth::guard('api')->user();

        // Only admins or owner can view
        if ($user->role !== 'ROLE_ADMIN' && $item->User_ID !== $user->id) {
            return new ServiceResponse(
                error: 'Unauthorized',
                status: 403 // Forbidden
            );
        }

        return new ServiceResponse(
            data: $item,
            message: 'Showing booking'
        );
    }

    // Update a booking (owner or admin)
    /**
     * @param array $validated
     * @param int $id
     * @return \App\Helpers\ServiceResponse
     */
    public function update(array $validated, int $id): ServiceResponse
    {
        $item = ($this->modelClass)::findOrFail($id);
        $user = Auth::guard('api')->user();

        if ($user->role !== 'ROLE_ADMIN' && $item->User_ID !== $user->id) {
            return new ServiceResponse(
                error: 'Unauthorized',
                status: 403 // Forbidden
            );
        }

        $startAt = $validated['Booking_StartAt'];
        $endAt = $validated['Booking_EndAt'];
        $providerId = $validated['Provider_ID'];

        // Prevent double booking: check overlapping bookings
        $exists = $this->modelClass::where('Provider_ID', $providerId)
            ->where('Booking_ID', '!=', $item->Booking_ID)
            ->where(function ($query) use ($startAt, $endAt) {
                $query->whereBetween('Booking_StartAt', [$startAt, $endAt])
                    ->orWhereBetween('Booking_EndAt', [$startAt, $endAt])
                    ->orWhere(function ($q) use ($startAt, $endAt) {
                        $q->where('Booking_StartAt', '<=', $startAt)
                            ->where('Booking_EndAt', '>=', $endAt);
                    });
            })
            ->exists();

        if ($exists) {
            return new ServiceResponse(
                error: 'This time slot is already booked.',
                status: 422 // Unprocessable Content
            );
        }

        $item->update($validated);

        return new ServiceResponse(
            data: $item,
            message: 'Booking updated successfully'
        );
    }

    // Delete a booking (admins only)
    /**
     * @param int $id
     * @return \App\Helpers\ServiceResponse
     */
    public function destroy(int $id): ServiceResponse
    {
        $booking = ($this->modelClass)::findOrFail($id);

        $user = Auth::guard('api')->user();

        // Only admin or owner can cancel
        if ($user->role !== 'ROLE_ADMIN' && $booking->User_ID !== $user->id) {
            return new ServiceResponse(
                error: 'Unauthorized',
                status: 403 // Forbidden
            );
        }

        $booking->delete();

        return new ServiceResponse(
            data: $booking,
            message: 'Deleted successfully'
        );
    }
}
