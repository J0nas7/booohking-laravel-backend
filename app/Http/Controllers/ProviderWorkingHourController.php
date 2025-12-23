<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ProviderWorkingHourDTORequest;
use App\Http\Requests\ProviderWorkingHoursPageRequest;
use App\Services\ProviderWorkingHourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProviderWorkingHourController extends Controller
{
    public function __construct(
        protected ProviderWorkingHourService $service
    ) {
        // Only admins can create/update/delete working hours
        $this->middleware('role:ROLE_ADMIN')->only(['store', 'update', 'destroy']);
    }

    // List working hours (optionally filtered by provider).
    /**
     * @param \App\Http\Requests\ProviderWorkingHoursPageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ProviderWorkingHoursPageRequest $request): JsonResponse
    {
        $result = $this->service->index(
            $request->validatedPagination(),
            $request->validatedFilters()
        );

        return ApiResponse::fromServiceResult($result);
    }

    // Show a single working hour entry.
    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->service->show($id);
        return ApiResponse::fromServiceResult($result);
    }

    // Create a new working hour entry (admins only).
    /**
     * @param \App\Http\Requests\ProviderWorkingHourDTORequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ProviderWorkingHourDTORequest $request): JsonResponse
    {
        $result = $this->service->store($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    // Update an existing working hour entry (admins only).
    /**
     * @param \App\Http\Requests\ProviderWorkingHourDTORequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ProviderWorkingHourDTORequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($request->validated(), $id);
        return ApiResponse::fromServiceResult($result);
    }

    // Delete a working hour entry (admins only).
    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->destroy($id);
        return ApiResponse::fromServiceResult($result);
    }
}
