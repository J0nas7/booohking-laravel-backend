<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class BookingController extends BaseController
{
    protected string $modelClass = Booking::class;

    protected array $with = ['user', 'provider.service', 'service'];

    protected function rules(): array
    {
        return [
            'User_ID' => 'required|exists:users,User_ID',
            'Provider_ID' => 'required|exists:providers,Provider_ID',
            'Service_ID' => 'required|exists:services,Service_ID',
            'Booking_StartAt' => 'required|date|after:now',
            'Booking_EndAt' => 'required|date|after:Booking_StartAt',
            'Booking_Status' => 'sometimes|in:booked,cancelled',
            'Booking_CancelledAt' => 'sometimes|date',
        ];
    }

    /**
     * ---- CUSTOM BUSINESS LOGIC ----
     */

    /**
     * Generate and return available booking slots for a given provider.
     *
     * This endpoint calculates 30-minute slots (or based on service duration if provided)
     * for the next 30 days, taking into account the provider's working hours
     * and any existing bookings. Only future time slots are returned.
     *
     * Example request:
     * GET /bookings/{provider}/available-slots?service_id=1
     *
     * @param Request $request The HTTP request instance, optionally containing:
     *                        - service_id: integer (optional) to adjust slot duration based on service
     * @param Provider $provider The ID of the provider for whom to generate available slots, retrieved with dependency injection by Laravel
     *
     * @return JsonResponse A JSON array of available slots with the following structure:
     * [
     *     [
     *         'date' => 'YYYY-MM-DD',
     *         'start' => 'HH:MM',
     *         'end' => 'HH:MM'
     *     ],
     *     ...
     * ]
     */
    public function availableSlots(Request $request, Provider $provider): JsonResponse
    {
        $serviceId = $request->input('service_id'); // optional
        $slotDuration = 30; // default, or get from service if needed

        // Pagination params
        $page = max((int) $request->query('page', 1), 1);
        $perPage = max((int) $request->query('perPage', 20), 1);

        $slots = \App\Services\BookingService::generateAvailableSlots($provider, 30, $slotDuration, $serviceId);

        $total = count($slots);
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedSlots = array_slice($slots, $offset, $perPage);

        return response()->json([
            "success" => true,
            "data" => $paginatedSlots,
            "total" => $total,
            "pagination" => [
                "total" => $total,
                "perPage" => $perPage,
                "currentPage" => $page,
                "lastPage" => $lastPage
            ]
        ]);
    }

    public function readBookingsByUserID(Request $request, User $user): JsonResponse
    {
        // Pagination
        $page = max((int) $request->query('page', 1), 1);
        $perPage = max((int) $request->query('perPage', 10), 1);

        // Build query: all bookings for this user
        $query = Booking::with($this->with)
            ->where('User_ID', $user->User_ID)
            ->orderBy('Booking_StartAt', 'desc');

        // Paginate results
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        // Empty case
        if ($paginated->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No bookings found for this user',
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'perPage' => $perPage,
                    'currentPage' => $page,
                    'lastPage' => 0,
                ]
            ], 404);
        }

        // Successful response
        return response()->json([
            'success' => true,
            'message' => 'Bookings found',
            'data' => $paginated->items(),
            'pagination' => [
                'total' => $paginated->total(),
                'perPage' => $paginated->perPage(),
                'currentPage' => $paginated->currentPage(),
                'lastPage' => $paginated->lastPage(),
            ]
        ]);
    }

    /**
     * ---- OVERRIDES OF BASECONTROLLER ----
     */

    // List all bookings for admins, or only user's bookings
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get pagination parameters from query string, default to page 1, 10 items per page
        $page = max((int) $request->query('page', 1), 1);
        $perPage = max((int) $request->query('perPage', 10), 1);

        $query = ($this->modelClass)::query()->with($this->with);

        // Non-admins see only their own bookings
        if ($user->role !== 'ROLE_ADMIN') {
            $query->where('User_ID', $user->User_ID);
        }

        // Paginate the results
        $paginated = $query->orderBy('Booking_StartAt', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    // Create a new booking (any authenticated user)
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $startAt = $data['Booking_StartAt'];
        $endAt = $data['Booking_EndAt'];
        $providerId = $data['Provider_ID'];

        // Prevent double booking: check overlapping bookings
        $exists = Booking::where('Provider_ID', $providerId)
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
            return response()->json(['error' => 'This time slot is already booked.'], 422);
        }

        $item = ($this->modelClass)::create($data);

        $this->afterStore($item);

        return response()->json($item, 201);
    }

    // View a single booking
    public function show(Request $request, int $id): JsonResponse
    {
        $item = ($this->modelClass)::with($this->with)->findOrFail($id);

        $user = $request->user();

        // Only admins or owner can view
        if ($user->role !== 'ROLE_ADMIN' && $item->User_ID !== $user->User_ID) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($item);
    }

    // Update a booking (owner or admin)
    public function update(Request $request, int $id): JsonResponse
    {
        $item = ($this->modelClass)::findOrFail($id);
        $user = $request->user();

        if ($user->role !== 'ROLE_ADMIN' && $item->User_ID !== $user->User_ID) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate($this->rules());

        $startAt = $data['Booking_StartAt'];
        $endAt = $data['Booking_EndAt'];
        $providerId = $data['Provider_ID'];

        // Prevent double booking: check overlapping bookings
        $exists = Booking::where('Provider_ID', $providerId)
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
            return response()->json(['error' => 'This time slot is already booked.'], 422);
        }

        $item->update($data);

        $this->afterUpdate($item);

        return response()->json($item);
    }

    // Delete a booking (admins only)
    public function destroy(Request $request, int $id): JsonResponse
    {
        $booking = ($this->modelClass)::findOrFail($id);

        $user = $request->user();

        // Only admin or owner can cancel
        if ($user->role !== 'ROLE_ADMIN' && $booking->User_ID !== $user->User_ID) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $booking->delete();

        $this->afterDestroy($booking);

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * ---- Cache clearing ----
     */

    protected function clearCache($resource): void
    {
        // Implement caching logic if needed
    }

    protected function afterStore($resource): void
    {
        // Optional hooks (notifications, logging, etc.)
    }

    protected function afterUpdate($resource): void
    {
        // Optional hooks
    }

    protected function afterDestroy($resource): void
    {
        // Optional hooks
    }
}
