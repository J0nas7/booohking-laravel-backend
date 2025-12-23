<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ProviderDTORequest;
use App\Http\Requests\ProvidersPageRequest;
use App\Models\Service;
use App\Services\ProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProviderController extends Controller
{
    public function __construct(
        protected ProviderService $providerService
    ) {
        // Only admins can create/update/delete providers
        $this->middleware('role:ROLE_ADMIN')->only(['store', 'update', 'destroy']);
    }

    // List all providers in a paginated list.
    /**
     * @param \App\Http\Requests\ProvidersPageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ProvidersPageRequest $request): JsonResponse
    {
        $result = $this->providerService->index($request->validatedPagination());
        return ApiResponse::fromServiceResult($result);
    }

    // List providers by service in a paginated list.
    /**
     * @param \App\Http\Requests\ProvidersPageRequest $request
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function readProvidersByServiceID(
        ProvidersPageRequest $request,
        Service $service
    ): JsonResponse {
        $result = $this->providerService->readByService(
            $request->validatedPagination(),
            $service
        );

        return ApiResponse::fromServiceResult($result);
    }

    // Show a single provider detailed.
    /**
     * @param int $id Provider primary key
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->providerService->show($id);
        return ApiResponse::fromServiceResult($result);
    }

    // Create a new provider. (admins-only)
    /**
     * @param \App\Http\Requests\ProviderDTORequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ProviderDTORequest $request): JsonResponse
    {
        $result = $this->providerService->store($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    // Update an existing provider. (admins-only)
    /**
     * @param \App\Http\Requests\ProviderDTORequest $request
     * @param int $id Provider primary key
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ProviderDTORequest $request, int $id): JsonResponse
    {
        $result = $this->providerService->update($request->validated(), $id);
        return ApiResponse::fromServiceResult($result);
    }

    // Delete a provider if no bookings exist. (admins-only)
    /**
     * @param int $id Provider primary key
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->providerService->destroy($id);
        return ApiResponse::fromServiceResult($result);
    }
}
