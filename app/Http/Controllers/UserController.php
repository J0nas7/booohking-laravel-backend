<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\{
    RegisterUserRequest
};
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
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
    public function index(): JsonResponse
    {
        $result = $this->userService->indexUsers();
        return ApiResponse::fromServiceResult($result);
    }

    // Store a new user.
    /**
     * @param $request
     * @return JsonResponse
     */
    public function store(RegisterUserRequest $request): JsonResponse
    {
        $result = $this->userService->storeUser($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    // Show a specific user
    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
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
        return ApiResponse::fromServiceResult($result);
    }

    // Delete a user
    /**
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->userService->destroyUser($id);
        return ApiResponse::fromServiceResult($result);
    }
}
