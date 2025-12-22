<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\AvailableSlotsRequest;
use App\Http\Requests\BookingDTORequest;
use App\Http\Requests\BookingsPageRequest;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BookingController extends Controller
{
    protected $bookingService;

    protected string $modelClass = Booking::class;

    protected function rules(): array
    {
        return [
            'User_ID' => 'required|exists:users,id',
            'Provider_ID' => 'required|exists:providers,Provider_ID',
            'Service_ID' => 'required|exists:services,Service_ID',
            'Booking_StartAt' => 'required|date|after:now',
            'Booking_EndAt' => 'required|date|after:Booking_StartAt',
            'Booking_Status' => 'sometimes|in:booked,cancelled',
            'Booking_CancelledAt' => 'sometimes|date',
        ];
    }

    /**
     * @param BookingService $bookingService
     * @return void
     */
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
        // $this->middleware('role:ROLE_ADMIN')->only(['show']);
        $this->middleware(
            'auth:api',
            ['except' => [
                'index',
                'store',
                'show',
            ]]
        );
    }

    // Generate and return available booking slots for a given provider.
    /**
     * @param \App\Http\Requests\AvailableSlotsRequest $request
     * @param \App\Models\Provider $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableSlots(AvailableSlotsRequest $request, Provider $provider): JsonResponse
    {
        $result = $this->bookingService->generateAvailableSlots($request->validatedWithDefaults(), $provider);
        return ApiResponse::fromServiceResult($result);
    }

    // Retrieve paginated bookings for a specific user.
    /**
     * @param BookingsPageRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function readBookingsByUserID(BookingsPageRequest $request, User $user): JsonResponse
    {
        $result = $this->bookingService->readBookingsByUserID($request->validatedPagination(), $user);
        return ApiResponse::fromServiceResult($result);
    }

    // List all bookings for admins, or only user's bookings
    /**
     * @param \App\Http\Requests\BookingsPageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(BookingsPageRequest $request): JsonResponse
    {
        $result = $this->bookingService->index($request->validatedPagination());
        return ApiResponse::fromServiceResult($result);
    }

    // Create a new booking (any authenticated user)
    /**
     * @param \App\Http\Requests\BookingDTORequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(BookingDTORequest $request): JsonResponse
    {
        $result = $this->bookingService->store($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    // View a single booking
    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->bookingService->show($id);
        return ApiResponse::fromServiceResult($result);
    }

    // Update a booking (owner or admin)
    /**
     * @param \App\Http\Requests\BookingDTORequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(BookingDTORequest $request, int $id): JsonResponse
    {
        $result = $this->bookingService->update($request->validated(), $id);
        return ApiResponse::fromServiceResult($result);
    }

    // Delete a booking (admins only)
    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->bookingService->destroy($id);
        return ApiResponse::fromServiceResult($result);
    }
}
