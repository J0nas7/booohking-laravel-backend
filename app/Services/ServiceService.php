<?php

namespace App\Services;

use App\Helpers\ServiceResponse;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ServiceService
{
    protected string $modelClass = Service::class;

    protected array $with = ['bookings', 'providers.workingHours'];

    // Retrieve all services (paginated).
    /**
     * @param array $validated
     * @return \App\Helpers\ServiceResponse
     */
    public function index(array $validated): ServiceResponse
    {
        ['page' => $page, 'perPage' => $perPage] = $validated;

        $paginated = ($this->modelClass)::with($this->with)
            ->orderBy('Service_Name')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ServiceResponse(
            data: [
                'data' => $paginated->items(),
                'pagination' => [
                    'total' => $paginated->total(),
                    'perPage' => $paginated->perPage(),
                    'currentPage' => $paginated->currentPage(),
                    'lastPage' => $paginated->lastPage(),
                ],
            ],
            message: 'Services found'
        );
    }

    // Retrieve services by user.
    /**
     * @param array $validated
     * @param \App\Models\User $user
     * @return \App\Helpers\ServiceResponse
     */
    public function readByUser(array $validated, User $user): ServiceResponse
    {
        ['page' => $page, 'perPage' => $perPage] = $validated;

        $paginated = ($this->modelClass)::with($this->with)
            ->where('User_ID', $user->id)
            ->orderBy('Service_Name')
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
                    ],
                ],
                message: 'No services found for this user',
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
                ],
            ],
            message: 'Services found'
        );
    }

    // Retrieve a single service.
    /**
     * @param int $id
     * @return \App\Helpers\ServiceResponse
     */
    public function show(int $id): ServiceResponse
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $cacheKey = "model:{$modelName}:{$id}";

        if ($cached = Cache::get($cacheKey)) {
            return new ServiceResponse(
                data: json_decode($cached, true),
                message: 'Service found (cached)'
            );
        }

        $item = ($this->modelClass)::with($this->with)->findOrFail($id);

        Cache::put($cacheKey, $item->toJson(), 3600);

        return new ServiceResponse(
            data: $item,
            message: 'Service found'
        );
    }

    // Create a service.
    /**
     * @param array $validated
     * @return \App\Helpers\ServiceResponse
     */
    public function store(array $validated): ServiceResponse
    {
        $item = ($this->modelClass)::create($validated);
        $this->clearCache($item);

        return new ServiceResponse(
            data: $item,
            message: 'Service created successfully',
            status: 201
        );
    }

    //Update a service.
    /**
     * @param array $validated
     * @param int $id
     * @return \App\Helpers\ServiceResponse
     */
    public function update(array $validated, int $id): ServiceResponse
    {
        $item = ($this->modelClass)::findOrFail($id);
        $item->update($validated);

        $this->clearCache($item);

        return new ServiceResponse(
            data: $item,
            message: 'Service updated successfully'
        );
    }

    // Delete a service.
    /**
     * @param int $id
     * @return \App\Helpers\ServiceResponse
     */
    public function destroy(int $id): ServiceResponse
    {
        $service = ($this->modelClass)::findOrFail($id);

        if ($service->bookings()->exists()) {
            return new ServiceResponse(
                error: 'Cannot delete service with existing bookings',
                status: 400
            );
        }

        $service->delete();
        $this->clearCache($service);

        return new ServiceResponse(
            data: $service,
            message: 'Service deleted successfully'
        );
    }

    // Clear service-related cache.
    /**
     * @param mixed $resource
     * @return void
     */
    protected function clearCache($resource): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));

        Cache::deleteMultiple([
            "model:{$modelName}:all",
            "model:{$modelName}:{$resource->Service_ID}",
        ]);
    }
}
