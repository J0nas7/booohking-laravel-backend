<?php

namespace App\Actions\RegisterUser;

use Illuminate\Support\Facades\Validator;

class ValidateRegistration
{
    public function execute(array $data)
    {
        $rules = [
            'acceptTerms'   => 'required|accepted',
            'User_Email'    => 'required|email|unique:Boo_Users,User_Email',
            'User_Password' => 'required|min:6|confirmed',
            'User_Name'     => 'required|string|max:255',
            'User_Role'     => 'nullable|in:ROLE_ADMIN,ROLE_USER',
        ];

        $validator = Validator::make($data, $rules);

        return $validator->fails()
            ? $validator->errors()
            : true;
    }
}
