<?php

namespace App\Actions\SendResetToken;

use App\Models\User;
use Illuminate\Support\Str;

class GenerateResetToken
{
    public function execute(User $user): string
    {
        $token = Str::random(16);
        $user->User_Remember_Token = $token;
        $user->save();

        return $token;
    }
}
