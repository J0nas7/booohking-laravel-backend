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
            'User_Name' => $data['User_Name'] ?? null,
            'email' => $data['User_Email'] ?? null,
            'User_Email' => $data['User_Email'] ?? null,
            'User_Password' => $data['User_Password'] ?? null,
        ];

        if (isset($userData['User_Password'])) {
            $userData['User_Password'] = $this->hasher->make($userData['User_Password']);
        }

        $userData['User_Role'] = $userData['User_Role'] ?? 'ROLE_USER';

        $user = User::create($userData);

        $user->email_verification_token = Str::random(16);
        $user->email_verified_at = null;
        $user->save();

        return $user;
    }
}
