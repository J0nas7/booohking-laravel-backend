<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ServiceController extends BaseController
{
    protected string $modelClass = Service::class;

    protected array $with = ['bookings', 'providers.workingHours'];

    protected function rules(): array
    {
        return [
            'Service_Name' => 'required|string|max:255',
            'User_ID' => 'required|exists:Boo_Users,User_ID',
            'Service_DurationMinutes' => 'required|integer|min:1',
            'Service_Description' => 'nullable|string',
        ];
    }

    public function __construct()
    {
        $this->middleware('role:ROLE_ADMIN')->only(['store', 'update', 'destroy']);
    }

    /**
     * ---- CUSTOM BUSINESS LOGIC ----
     */
    public function readServicesByUserId(Request $request, User $user): JsonResponse
    {
        // Pagination
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = Service::with($this->with)
            ->where('User_ID', $user->User_ID)
            ->orderBy('Service_Name');

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        if ($paginated->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No services found for this user',
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
            'message' => 'Services found',
            'data' => $paginated->items(),
            'pagination' => [
                'total' => $paginated->total(),
                'perPage' => $paginated->perPage(),
                'currentPage' => $paginated->currentPage(),
                'lastPage' => $paginated->lastPage(),
            ]
        ]);
    }

    // Anyone authenticated can see the list of services. Could even be public.
    public function index(Request $request): JsonResponse
    {
        // Get pagination parameters from query string, defaults: page=1, perPage=10
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = ($this->modelClass)::query();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        // Paginate results
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    // Only admins should be able to create new services.
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $item = ($this->modelClass)::create($data);

        $this->afterStore($item);

        return response()->json($item, 201);
    }

    // Anyone authenticated can view a service.
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

    // Only admins can modify services.
    public function update(Request $request, int $id): JsonResponse
    {
        $item = ($this->modelClass)::findOrFail($id);

        $data = $request->validate($this->rules());

        $item->update($data);

        $this->afterUpdate($item);

        return response()->json($item);
    }

    // Only admins can delete services.
    public function destroy(Request $request, int $id): JsonResponse
    {
        $service = ($this->modelClass)::findOrFail($id);

        // Check if service has any bookings
        if ($service->bookings()->exists()) {
            return response()->json([
                'message' => 'Cannot delete service with existing bookings'
            ], 400);
        }

        $service->delete();

        $this->afterDestroy($service);

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
