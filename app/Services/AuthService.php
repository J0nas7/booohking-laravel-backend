<?php

namespace App\Services;

use App\Mail\ForgotPasswordMail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

trait AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function registerUser(array $data)
    {
        // Define validation rules
        $rules = [
            'acceptTerms'    => 'required|accepted', // must be yes/on/1/true
            'User_Email'      => 'required|email|unique:Boo_Users,User_Email',
            'User_Password'   => 'required|min:6|confirmed', // expects userPassword_confirmation
            'User_Name'      => 'required|string|max:255',
            'User_Role'       => 'nullable|in:ROLE_ADMIN,ROLE_USER', // optional, defaults to ROLE_USER
        ];

        // Run validation
        $validator = Validator::make($data, $rules);

        // Return validation errors if any
        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        // Determine which keys exist in the DB (fillable)
        $fillable = (new User())->getFillable();
        $userData = [];

        foreach ($fillable as $column) {
            if (isset($data[$column])) {
                $userData[$column] = $data[$column];
            }
        }

        // Hash password if present
        if (isset($userData['User_Password'])) {
            $userData['User_Password'] = Hash::make($userData['User_Password']);
        }

        // Set default role if not provided
        if (!isset($userData['User_Role'])) {
            $userData['User_Role'] = 'ROLE_USER';
        }

        // Create the user
        $user = User::create($userData);

        // Generate verification token
        $registerToken = Str::random(16);
        $user->User_Email_Verification_Token = $registerToken;
        $user->User_Email_VerifiedAt = null;
        $user->save();

        // -------------------------------
        // Send welcome email
        // -------------------------------
        try {
            Mail::to($user->User_Email)->send(new WelcomeEmail($user, $registerToken));
            $emailStatus = 'Email sent successfully.';
            $registerToken = "";
        } catch (\Exception $e) {
            // Log the error and still return success
            Log::error('Failed to send registration email: ' . $e->getMessage());
            $emailStatus = 'Failed to send email: ' . $e->getMessage();
        }

        // Return success response
        return [
            'success' => true,
            'message' => 'User was created.',
            'email_status' => $emailStatus,
            'user' => $user,
            'token' => $registerToken
        ];
    }

    /**
     * Authenticate a user and generate a JWT.
     *
     * @param array $credentials
     * @return array
     */
    public function authenticateUser(array $credentials)
    {
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return ['error' => 'Invalid email or password'];
        }

        // Get the authenticated user
        $user = Auth::guard('api')->user();

        // Check if email is verified
        if (!$user->User_Email_VerifiedAt) {
            return ['error' => 'Please verify your email before logging in.'];
        }

        return [
            'success' => true,
            'message' => 'Login was successful',
            'data' => [
                'user' => Auth::guard('api')->user(),
                'accessToken' => $token
            ]
        ];
    }

    public function sendResetToken(array $data)
    {
        $validator = Validator::make($data, [
            'User_Email' => 'required|email|exists:Boo_Users,User_Email',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        // Generate 16-character alphanumeric token
        $token = Str::random(16);

        // Update user with the reset token
        $user = User::where('User_Email', $data['User_Email'])->first();
        $user->User_Remember_Token = $token;
        $user->save();

        // Send email using Mailable
        try {
            Mail::to($user->User_Email)->send(new ForgotPasswordMail($user, $token));
            $emailStatus = 'Email sent successfully.';
            $token = "";
        } catch (\Exception $e) {
            // Log the error and still return success
            Log::error('Failed to send registration email: ' . $e->getMessage());
            $emailStatus = 'Failed to send email: ' . $e->getMessage();
        }

        // Return success response
        return [
            'success' => true,
            'message' => 'Password reset token sent.',
            'email_status' => $emailStatus,
            'user' => $user,
            'token' => $token
        ];
    }

    public function resetPasswordWithToken(array $data)
    {
        $validator = Validator::make($data, [
            'User_Remember_Token' => 'required|string|size:16',
            'New_User_Password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        $user = User::where('User_Remember_Token', $data['User_Remember_Token'])->first();

        if (!$user) {
            return ['error' => 'Invalid token.'];
        }

        // Update the password
        $user->User_Password = Hash::make($data['New_User_Password']);
        $user->User_Remember_Token = null; // Clear token
        $user->save();

        return ['success' => true, 'message' => 'Password has been reset successfully.'];
    }

    /**
     * Logout the authenticated user.
     *
     * @return bool
     */
    public function logoutUser()
    {
        Auth::guard('api')->logout();
        return true;
    }

    /**
     * Get the authenticated user.
     *
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        $authUser = Auth::guard('api')->user();
        return $authUser;
    }
}
