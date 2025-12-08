<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class UserController extends BaseController
{
    use AuthService;

    protected string $modelClass = User::class;

    protected array $with = [];

    /**
     * Validation rules for creating/updating a user.
     */
    protected function rules(): array
    {
        return [
            'User_Name'    => 'required|string|max:255',
            'User_Email'   => 'required|email|unique:Boo_Users,User_Email',
            'User_Password' => 'min:6|confirmed', // expects User_Password_confirmation
            'User_Role'    => 'nullable|in:ROLE_ADMIN,ROLE_USER',
        ];
    }

    public function __construct()
    {
        $this->middleware('role:ROLE_ADMIN')->only(['show']);
    }

    /**
     * List all users (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        $users = ($this->modelClass)::with($this->with)->get();
        return response()->json($users);
    }

    /**
     * Store a new user
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        // Hash the password
        $data['User_Password'] = Hash::make($data['User_Password']);

        $user = ($this->modelClass)::create($data);

        $this->afterStore($user);

        return response()->json($user, 201);
    }

    /**
     * Show a specific user
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = ($this->modelClass)::with($this->with)->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update a user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = ($this->modelClass)::findOrFail($id);

        $data = $request->validate($this->rules());

        // Hash password if present
        if (isset($data['User_Password'])) {
            $data['User_Password'] = Hash::make($data['User_Password']);
        }

        $user->update($data);

        $this->afterUpdate($user);

        return response()->json($user);
    }

    /**
     * Delete a user
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = ($this->modelClass)::findOrFail($id);

        $user->delete();

        $this->afterDestroy($user);

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * Optional hooks
     */
    protected function clearCache($resource): void {}
    protected function afterStore($resource): void {}
    protected function afterUpdate($resource): void {}
    protected function afterDestroy($resource): void {}
}
