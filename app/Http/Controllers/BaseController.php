<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller as LaravelController;

/**
 * @method \Illuminate\Routing\MiddlewarePriorityQueue middleware(string|array $middleware, array $options = [])
 */
abstract class BaseController extends LaravelController
{
    /**
     * The model class associated with the controller.
     */
    protected string $modelClass;

    /**
     * Relationships to eager load.
     */
    protected array $with = [];

    /**
     * Define the validation rules for the resource.
     */
    abstract protected function rules(): array;

    /**
     * =========================
     * CRUD CONTRACT DEFINITIONS
     * =========================
     */

    /**
     * Retrieve a listing of the resource.
     */
    abstract public function index(Request $request): JsonResponse;

    /**
     * Store a newly created resource in storage.
     */
    abstract public function store(Request $request): JsonResponse;

    /**
     * Display the specified resource.
     */
    abstract public function show(Request $request, int $id): JsonResponse;

    /**
     * Update the specified resource in storage.
     */
    abstract public function update(Request $request, int $id): JsonResponse;

    /**
     * Remove the specified resource from storage.
     */
    abstract public function destroy(Request $request, int $id): JsonResponse;

    /**
     * =========================
     * HELPERS / HOOKS
     * =========================
     */

    protected function getFieldName(string $field): string
    {
        $modelName = class_basename($this->modelClass);
        $prefix = Str::singular($modelName) . '_';
        return $prefix . $field;
    }

    abstract protected function clearCache(Model $resource): void;
    abstract protected function afterStore(Model $resource): void;
    abstract protected function afterUpdate(Model $resource): void;
    abstract protected function afterDestroy(Model $resource): void;
}
