<?php

namespace App\Services;

use App\Actions\RegisterUser\RegisterUser;
use App\Helpers\ServiceResponse;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected Mailer $mail;
    protected Hasher $hasher;

    protected string $modelClass = User::class;

    protected array $with = [];

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
     * @param \Illuminate\Contracts\Mail\Mailer $mail
     * @param \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param \App\Actions\RegisterUser\RegisterUser $registerUser
     * @return void
     */
    public function __construct(
        Mailer $mail,
        Hasher $hasher,
        protected RegisterUser $registerUser
    ) {
        $this->mail = $mail;
        $this->hasher = $hasher;
    }

    // List all users (admin only).
    /**
     * @param array $validated
     * @return ServiceResponse
     */
    public function indexUsers(): ServiceResponse
    {
        $users = ($this->modelClass)::with($this->with)->get();
        return new ServiceResponse(
            data: $users,
            message: 'Users listing',
            status: 200
        );
    }

    // Store a new user, using action delegate.
    /**
     * @param array $validated
     * @return ServiceResponse
     */
    public function storeUser(array $validated): ServiceResponse
    {
        return $this->registerUser->execute($validated);
    }

    // Show a specific user.
    /**
     * @param array $validated
     * @param int $id
     * @return ServiceResponse
     */
    public function showUser(int $id): ServiceResponse
    {
        $user = ($this->modelClass)::with($this->with)->findOrFail($id);
        return new ServiceResponse(
            data: $user,
            message: 'User found',
            status: 200
        );
    }

    // Update a user.
    /**
     * @param array $validated
     * @param int $id
     * @return ServiceResponse
     */
    public function updateUser(array $validated, int $id): ServiceResponse
    {
        $user = ($this->modelClass)::findOrFail($id);

        // Hash password if present
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return new ServiceResponse(
            data: $user,
            message: 'User updated',
            status: 200
        );
    }

    public function destroyUser(int $id): ServiceResponse
    {
        $user = ($this->modelClass)::findOrFail($id);

        $user->delete();

        return new ServiceResponse(
            data: $user,
            message: 'User deleted',
            status: 200
        );
    }
}
