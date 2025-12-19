<?php

namespace App\Actions\RegisterUser;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;

class CreateUser
{
    public function __construct(
        protected Hasher $hasher
    ) {}

    public function execute(array $data): User
    {
        $userData = [
            'name' => $data['name'] ?? null,
            'email' => $data['User_Email'] ?? null,
            'User_Email' => $data['User_Email'] ?? null,
            'password' => $data['password'] ?? null,
        ];

        if (isset($userData['password'])) {
            $userData['password'] = $this->hasher->make($userData['password']);
        }

        $userData['role'] = $userData['role'] ?? 'ROLE_USER';

        $user = User::create($userData);

        $user->email_verification_token = Str::random(16);
        $user->email_verified_at = null;
        $user->save();

        return $user;
    }
}
