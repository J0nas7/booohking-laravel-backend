<?php

namespace App\Http\Controllers;

use App\Models\ProviderWorkingHour;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProviderWorkingHourController extends BaseController
{
    protected string $modelClass = ProviderWorkingHour::class;

    protected array $with = ['provider'];

    /**
     * Validation rules for ProviderWorkingHour.
     */
    protected function rules(): array
    {
        return [
            'Provider_ID' => 'required|exists:Boo_Providers,Provider_ID',
            'PWH_DayOfWeek' => 'required|integer|min:0|max:6', // 0=Sunday, 6=Saturday
            'PWH_StartTime' => 'required|date_format:H:i',
            'PWH_EndTime' => 'required|date_format:H:i|after:StartTime',
        ];
    }

    public function __construct()
    {
        // Only admins can create/update/delete working hours
        $this->middleware('role:ROLE_ADMIN')->only(['store', 'update', 'destroy']);
    }

    /**
     * List all working hours, optionally filter by provider.
     */
    public function index(Request $request): JsonResponse
    {
        // Get pagination parameters from query string, default page 1, 10 items per page
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = ($this->modelClass)::with($this->with);

        if ($request->has('Provider_ID')) {
            $query->where('Provider_ID', $request->Provider_ID);
        }

        // Optional: order by day of week and start time for consistency
        $query->orderBy('PWH_DayOfWeek')->orderBy('PWH_StartTime');

        // Paginate
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    /**
     * Create new working hour entry (admins only).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $item = ($this->modelClass)::create($data);

        $this->afterStore($item);

        return response()->json($item, 201);
    }

    /**
     * Show a single working hour entry.
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
     * Update a working hour entry (admins only).
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
     * Delete a working hour entry (admins only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = ($this->modelClass)::findOrFail($id);

        $item->delete();

        $this->afterDestroy($item);

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
