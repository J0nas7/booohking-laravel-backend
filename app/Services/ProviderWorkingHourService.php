<?php

namespace App\Services;

use App\Helpers\ServiceResponse;
use App\Models\ProviderWorkingHour;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProviderWorkingHourService
{
    protected string $modelClass = ProviderWorkingHour::class;

    protected array $with = ['provider'];

    // Paginated list of working hours.
    /**
     * @param array $pagination
     * @param array $filters
     * @return \App\Helpers\ServiceResponse
     */
    public function index(array $pagination, array $filters): ServiceResponse
    {
        ['page' => $page, 'perPage' => $perPage] = $pagination;

        $query = ($this->modelClass)::with($this->with)
            ->orderBy('PWH_DayOfWeek')
            ->orderBy('PWH_StartTime');

        if (!empty($filters['Provider_ID'])) {
            $query->where('Provider_ID', $filters['Provider_ID']);
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return new ServiceResponse(
            data: [
                'data' => $paginated->items(),
                'pagination' => [
                    'total'        => $paginated->total(),
                    'perPage'      => $paginated->perPage(),
                    'currentPage'  => $paginated->currentPage(),
                    'lastPage'     => $paginated->lastPage(),
                ],
            ],
            message: 'Working hours found'
        );
    }

    // Show a single working hour.
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
                message: 'Working hour found (cached)'
            );
        }

        $item = ($this->modelClass)::with($this->with)->findOrFail($id);

        Cache::put($cacheKey, $item->toJson(), 3600);

        return new ServiceResponse(
            data: $item,
            message: 'Working hour found'
        );
    }

    // Create working hour.
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
            message: 'Working hour created successfully',
            status: 201
        );
    }

    // Update working hour.
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
            message: 'Working hour updated successfully'
        );
    }

    // Delete working hour.
    /**
     * @param int $id
     * @return \App\Helpers\ServiceResponse
     */
    public function destroy(int $id): ServiceResponse
    {
        $item = ($this->modelClass)::findOrFail($id);
        $item->delete();

        $this->clearCache($item);

        return new ServiceResponse(
            data: $item,
            message: 'Deleted successfully'
        );
    }

    // Clear provider-working-hour-related cache entries.
    /**
     * @param mixed $resource
     * @return void
     */
    protected function clearCache($resource): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));

        Cache::deleteMultiple([
            "model:{$modelName}:all",
            "model:{$modelName}:{$resource->PWH_ID}",
        ]);
    }
}
