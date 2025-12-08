<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProviderController extends BaseController
{
    protected string $modelClass = Provider::class;

    protected array $with = ['workingHours', 'bookings.service', 'service'];

    /**
     * Validation rules for Provider.
     */
    protected function rules(): array
    {
        return [
            'Provider_Name' => 'required|string|max:255',
            'Service_ID' => 'required|exists:Boo_Services,Service_ID',
            // Optional: validate working hours if you allow input directly
            // 'Working_Hours' => 'array',
        ];
    }

    /**
     * ---- CUSTOM BUSINESS LOGIC ----
     */
    public function readProvidersByServiceID(Request $request, Service $service): JsonResponse
    {
        // Pagination
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        // Query providers that have the service
        $query = Provider::with($this->with)
            ->where('Service_ID', $service->Service_ID)
            ->orderBy('Provider_Name');

        // Paginate
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        if ($paginated->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No providers found for this service',
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'perPage' => $perPage,
                    'currentPage' => $page,
                    'lastPage' => 0,
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Providers found',
            'data' => $paginated->items(),
            'pagination' => [
                'total' => $paginated->total(),
                'perPage' => $paginated->perPage(),
                'currentPage' => $paginated->currentPage(),
                'lastPage' => $paginated->lastPage(),
            ]
        ]);
    }

    public function __construct()
    {
        // Only admins can create/update/delete providers
        $this->middleware('role:ROLE_ADMIN')->only(['store', 'update', 'destroy']);
    }

    /**
     * List all providers with optional eager loading.
     */
    public function index(Request $request): JsonResponse
    {
        // Get pagination parameters from query string, default page 1, 10 items per page
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = ($this->modelClass)::query()->with($this->with);

        // Paginate the results
        $paginated = $query->orderBy('Provider_Name')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    /**
     * Create a new provider (admins only).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $item = ($this->modelClass)::create($data);

        $this->afterStore($item);

        return response()->json($item, 201);
    }

    /**
     * Show a single provider.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $cacheKey = "model:{$modelName}:{$id}";

        // Check cache
        $cachedResource = Cache::get($cacheKey);
        if ($cachedResource) {
            return response()->json(json_decode($cachedResource, true));
        }

        $item = ($this->modelClass)::with($this->with)->findOrFail($id);

        // Cache for 1 hour
        Cache::put($cacheKey, $item->toJson(), 3600);

        return response()->json($item);
    }

    /**
     * Update a provider (admins only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = ($this->modelClass)::findOrFail($id);

        $data = $request->validate($this->rules());

        $item->update($data);

        $this->afterUpdate($item);

        return response()->json($item);
    }

    /**
     * Delete a provider (admins only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $provider = ($this->modelClass)::findOrFail($id);

        // Check if provider has any bookings
        if ($provider->bookings()->exists()) {
            return response()->json([
                'message' => 'Cannot delete provider with existing bookings'
            ], 400);
        }

        $provider->delete();

        $this->afterDestroy($provider);

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * Cache clearing.
     */
    protected function clearCache($resource): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$this->getFieldName('ID')}",
        ];

        Cache::deleteMultiple($keys);
    }

    protected function afterStore($resource): void
    {
        $this->clearCache($resource);
    }

    protected function afterUpdate($resource): void
    {
        $this->clearCache($resource);
    }

    protected function afterDestroy($resource): void
    {
        $this->clearCache($resource);
    }
}
