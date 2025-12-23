<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ServiceDTORequest;
use App\Http\Requests\ServicesPageRequest;
use App\Models\User;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ServiceController extends Controller
{
    public function __construct(
        protected ServiceService $serviceService
    ) {
        // Only admins can create/update/delete services
        $this->middleware('role:ROLE_ADMIN')->only(['store', 'update', 'destroy']);
    }

    // List all services (paginated).
    /**
     * @param \App\Http\Requests\ServicesPageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ServicesPageRequest $request): JsonResponse
    {
        $result = $this->serviceService->index(
            $request->validatedPagination()
        );

        return ApiResponse::fromServiceResult($result);
    }

    // List services by user (paginated).
    /**
     * @param \App\Http\Requests\ServicesPageRequest $request
     * @param \App\Models\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function readServicesByUserId(
        ServicesPageRequest $request,
        User $user
    ): JsonResponse {
        $result = $this->serviceService->readByUser(
            $request->validatedPagination(),
            $user
        );

        return ApiResponse::fromServiceResult($result);
    }

    // Show a single service.
    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->serviceService->show($id);
        return ApiResponse::fromServiceResult($result);
    }

    // Create a new service (admins only).
    /**
     * @param \App\Http\Requests\ServiceDTORequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ServiceDTORequest $request): JsonResponse
    {
        $result = $this->serviceService->store(
            $request->validated()
        );

        return ApiResponse::fromServiceResult($result);
    }

    // Update a service (admins only).
    /**
     * @param \App\Http\Requests\ServiceDTORequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ServiceDTORequest $request, int $id): JsonResponse
    {
        $result = $this->serviceService->update(
            $request->validated(),
            $id
        );

        return ApiResponse::fromServiceResult($result);
    }

    // Delete a service (admins only).
    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->serviceService->destroy($id);
        return ApiResponse::fromServiceResult($result);
    }
}
