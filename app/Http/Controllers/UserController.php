<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\{
    RegisterUserRequest
};
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    protected string $modelClass = User::class;

    protected array $with = [];

    protected $userService;

    /**
     * Validation rules for creating/updating a user.
     */
    protected function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email',
            'password' => 'min:6|confirmed', // expects password_confirmation
            'role'    => 'nullable|in:ROLE_ADMIN,ROLE_USER',
        ];
    }

    /**
     * @param UserService $userService
     * @return void
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('role:ROLE_ADMIN')->only(['show']);
        $this->middleware(
            'auth:api',
            ['except' => [
                'index',
                'store',
                'show',
            ]]
        );
    }

    // List all users (admin only)
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->userService->indexUsers();
        return ApiResponse::fromServiceResult($result);
    }

    // Store a new user.
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        /** @var RegisterUserRequest $request */
        $validated = $request->validated();
        $result = $this->userService->storeUser($validated);
        $this->afterStore($result->data['user']);
        return ApiResponse::fromServiceResult($result);
    }

    // Show a specific user
    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $result = $this->userService->showUser($id);
        return ApiResponse::fromServiceResult($result);
    }

    // Update a user
    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $result = $this->userService->updateUser($validated, $id);
        $this->afterUpdate($result->data['user']);
        return ApiResponse::fromServiceResult($result);
    }

    // Delete a user
    /**
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $result = $this->userService->destroyUser($id);
        $this->afterDestroy($result->data['user']);
        return ApiResponse::fromServiceResult($result);
    }

    /**
     * Optional hooks
     */
    protected function clearCache($resource): void {}
    protected function afterStore($resource): void {}
    protected function afterUpdate($resource): void {}
    protected function afterDestroy($resource): void {}
}
