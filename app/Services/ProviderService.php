<?php

namespace App\Services;

use App\Helpers\ServiceResponse;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProviderService
{
    protected string $modelClass = Provider::class;

    protected array $with = ['workingHours', 'bookings.service', 'service'];

    // Retrieve a paginated list of all providers.
    /**
     * @param array $validated Pagination data (page, perPage)
     * @return \App\Helpers\ServiceResponse
     */
    public function index(array $validated): ServiceResponse
    {
        ['page' => $page, 'perPage' => $perPage] = $validated;

        $paginated = ($this->modelClass)::with($this->with)
            ->orderBy('Provider_Name')
            ->paginate($perPage, ['*'], 'page', $page);

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
            message: 'Providers found'
        );
    }

    // Retrieve paginated providers for a specific service.
    /**
     * @param array $validated Pagination data (page, perPage)
     * @param \App\Models\Service $service
     * @return \App\Helpers\ServiceResponse
     */
    public function readByService(array $validated, Service $service): ServiceResponse
    {
        ['page' => $page, 'perPage' => $perPage] = $validated;

        $paginated = ($this->modelClass)::with($this->with)
            ->where('Service_ID', $service->Service_ID)
            ->orderBy('Provider_Name')
            ->paginate($perPage, ['*'], 'page', $page);

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
                message: 'No providers found for this service',
                status: 404
            );
        }

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
            message: 'Providers found'
        );
    }

    // Retrieve a single provider by ID.
    /**
     * @param int $id Provider primary key
     * @return \App\Helpers\ServiceResponse
     */
    public function show(int $id): ServiceResponse
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $cacheKey = "model:{$modelName}:{$id}";

        if ($cached = Cache::get($cacheKey)) {
            return new ServiceResponse(
                data: json_decode($cached, true),
                message: 'Provider found (cached)'
            );
        }

        $item = ($this->modelClass)::with($this->with)->findOrFail($id);

        Cache::put($cacheKey, $item->toJson(), 3600);

        return new ServiceResponse(
            data: $item,
            message: 'Provider found'
        );
    }

    // Create a new provider.
    /**
     * @param array $validated Validated provider data
     * @return \App\Helpers\ServiceResponse
     */
    public function store(array $validated): ServiceResponse
    {
        $item = ($this->modelClass)::create($validated);
        $this->clearCache($item);

        return new ServiceResponse(
            data: $item,
            message: 'Provider created successfully',
            status: 201
        );
    }

    // Update an existing provider.
    /**
     * @param array $validated Validated provider data
     * @param int $id Provider primary key
     * @return \App\Helpers\ServiceResponse
     */
    public function update(array $validated, int $id): ServiceResponse
    {
        $item = ($this->modelClass)::findOrFail($id);
        $item->update($validated);

        $this->clearCache($item);

        return new ServiceResponse(
            data: $item,
            message: 'Provider updated successfully'
        );
    }

    // Delete a provider.
    /**
     * Deletes a provider if no bookings exist.
     * Prevents deletion when related bookings are present.
     *
     * @param int $id Provider primary key
     * @return \App\Helpers\ServiceResponse
     */
    public function destroy(int $id): ServiceResponse
    {
        $provider = ($this->modelClass)::findOrFail($id);

        if ($provider->bookings()->exists()) {
            return new ServiceResponse(
                error: 'Cannot delete provider with existing bookings',
                status: 400
            );
        }

        $provider->delete();
        $this->clearCache($provider);

        return new ServiceResponse(
            data: $provider,
            message: 'Deleted successfully'
        );
    }

    // Clear provider-related cache entries.
    /**
     * Removes cached provider collections and forces fresh reads.
     *
     * @param mixed $resource
     * @return void
     */
    protected function clearCache($resource): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$resource->Provider_ID}"
        ];
        Cache::deleteMultiple($keys);
    }
}
