<?php

namespace App\Services;

use App\Actions\RegisterUser\RegisterUser;
use App\Helpers\ApiResponse;
use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Hashing\Hasher;
use App\Helpers\ServiceResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthService
{
    protected Mailer $mail;
    protected Hasher $hasher;

    // Dependency Injection
    public function __construct(
        Mailer $mail,
        Hasher $hasher,
        protected RegisterUser $registerUser
    ) {
        $this->mail = $mail;
        $this->hasher = $hasher;
    }

    // Registers a new user, using action delegate.
    /**
     * @param array $data
     * @return ServiceResponse
     */
    public function registerUser(array $data): ServiceResponse
    {
        return $this->registerUser->execute($data);
    }

    // Activate the user's email account using the verification token.
    /**
     * @param array $validated
     * @return ServiceResponse
     */
    public function activateAccount(array $validated): ServiceResponse
    {
        // Find the user by verification token
        $user = User::where('User_Email_Verification_Token', $validated['token'])->first();

        if (!$user) {
            return new ServiceResponse(
                error: 'Invalid verification token'
            );
        }

        // Update user email verification details
        $user->User_Email_VerifiedAt = now();
        $user->User_Email_Verification_Token = null;
        $user->save();

        // Return a success message
        return new ServiceResponse(
            message: 'Email verified successfully'
        );
    }

    // Authenticate a user and generate a JWT.
    /**
     * @param array $credentials
     * @return ServiceResponse
     */
    public function authenticateUser(array $credentials): ServiceResponse
    {
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return new ServiceResponse(
                error: 'Invalid email or password',
                status: 401
            );
        }

        // Get the authenticated user
        $user = Auth::guard('api')->user();

        // Check if email is verified
        if (!$user->User_Email_VerifiedAt) {
            return new ServiceResponse(
                error: 'Please verify your email before logging in.',
                status: 401
            );
        }

        return new ServiceResponse(
            data: [
                'user' => Auth::guard('api')->user(),
                'accessToken' => $token
            ],
            message: 'User logged in successfully'
        );
    }

    // Sends a password reset token to the user's email address.
    /**
     * @param array $data
     * @return ServiceResponse
     */
    public function sendResetToken(array $validated): ServiceResponse
    {
        try {
            // Generate 16-character alphanumeric token
            $token = Str::random(16);

            // Update user with the reset token
            $user = User::where('User_Email', $validated['User_Email'])->firstOrFail();
            $user->User_Remember_Token = $token;
            $user->save();

            // Send email using Mailable
            try {
                $this->mail->to($user->User_Email)->send(new ForgotPasswordMail($user, $token));
                $emailStatus = 'Email sent successfully.';
                $token = "";
            } catch (\Exception $e) {
                // Log the error and still return success
                Log::error('Failed to send registration email: ' . $e->getMessage());
                $emailStatus = 'Failed to send email: ' . $e->getMessage();
            }

            // Return success response
            return new ServiceResponse(
                data: [
                    'email_status' => $emailStatus,
                    'user' => $user,
                    'token' => $token
                ],
                message: 'Password reset token sent.',
            );
        } catch (ModelNotFoundException $e) {
            // If user is not found, return an error response with a 404 status
            return new ServiceResponse(
                errors: ['User_Email' => 'User not found.'],
                status: 404
            );
        }
    }

    // Resets the user's password using a provided reset token.
    /**
     * @param array $data
     * @return ServiceResponse
     */
    public function resetPasswordWithToken(array $data): ServiceResponse
    {
        // Define validation rules
        $rules = [
            'User_Remember_Token' => 'required|string|size:16',
            'New_User_Password' => 'required|string|min:6|confirmed',
        ];

        // Run validation
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return new ServiceResponse(
                errors: $validator->errors()->toArray(),
                status: 400
            );
        }

        $user = User::where('User_Remember_Token', $data['User_Remember_Token'])->first();

        if (!$user) {
            return new ServiceResponse(
                error: 'Invalid token.',
                status: 401
            );
        }

        // Update the password
        $user->User_Password = $this->hasher->make($data['New_User_Password']);
        $user->User_Remember_Token = null; // Clear token
        $user->save();

        return new ServiceResponse(
            message: 'Password reset successfully'
        );
    }

    // Generates a new access token for the authenticated user.
    /**
     * @return ServiceResponse
     */
    public function cloneToken(): ServiceResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return new ServiceResponse(
                error: 'Invalid or expired token',
                status: 401
            );
        }

        $newToken = JWTAuth::fromUser($user);

        return new ServiceResponse(
            data: [
                'user' => $user,
                'accessToken' => $newToken
            ],
            message: 'New token generated successfully'
        );
    }

    // TODO TESTING
    // Refreshes the current JWT token.
    /**
     * @return ServiceResponse
     */
    public function refreshJWT(): ServiceResponse
    {
        // Get the current token from header
        $token = JWTAuth::getToken();

        if (!$token) {
            return new ServiceResponse(
                error: 'Token not provided',
                status: 401
            );
        }

        // Refresh the token
        $newToken = JWTAuth::refresh($token);

        return new ServiceResponse(
            data: [
                'accessToken' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
            message: 'Token refreshed successfully'
        );
    }

    // Logout the authenticated user.
    /**
     * @return ServiceResponse
     */
    public function logoutUser(): ServiceResponse
    {
        Auth::guard('api')->logout();
        return new ServiceResponse(
            message: 'Logged out successfully'
        );
    }

    // Get the authenticated user.
    /**
     * @return ServiceResponse
     */
    public function getAuthenticatedUser(): ServiceResponse
    {
        $authUser = Auth::guard('api')->user();
        if (!$authUser) {
            return new ServiceResponse(
                error: 'Not authenticated',
                status: 401
            );
        }

        return new ServiceResponse(
            data: [
                'user' => $authUser
            ],
            message: 'Is logged in'
        );
    }

    // ----------
    // CACHE
    // ----------
    // TODO TESTING
    // Retrieves user data from the cache.
    /**
     * @param ServiceResponse $result
     * @return ServiceResponse|null
     */
    public function getUserFromCache(ServiceResponse $result): ServiceResponse|null
    {
        $userId = $result->data['user']->User_ID;
        $cacheKey = 'user:me:' . $userId;
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            return null; // cache miss
        }

        // Wrap cached data in a ServiceResponse
        return new ServiceResponse(
            data: $cachedData['data'],
            message: $cachedData['message']
        );
    }

    // TODO TESTING
    // Stores user data in the cache.
    /**
     * @param ServiceResponse $result
     * @param int $cacheTime
     * @return void
     */
    public function storeUserInCache(ServiceResponse $result, $cacheTime = 900)
    {
        $userId = $result->data['user']->User_ID;
        $cacheKey = 'user:me:' . $userId;
        $cacheData = [
            'data' => $result->data,
            'message' => $result->message,
        ];
        Cache::put($cacheKey, $cacheData, $cacheTime);
    }

    // TODO TESTING
    // Removes the user data from the cache.
    /**
     * @param ServiceResponse $result
     * @return void
     */
    public function forgetUserFromCache(ServiceResponse $result)
    {
        $userId = $result->data['user']->User_ID;
        $cacheKey = 'user:me:' . $userId;
        Cache::forget($cacheKey);
    }
}
