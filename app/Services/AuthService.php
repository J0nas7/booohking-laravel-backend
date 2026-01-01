<?php

namespace App\Services;

use App\Actions\{
    SendResetToken\SendResetToken,
};
use Illuminate\Support\Facades\{
    Auth,
    Cache,
    Password,
};
use Illuminate\Contracts\{
    Mail\Mailer,
    Hashing\Hasher,
};
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\ServiceResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthService
{
    protected Mailer $mail;
    protected Hasher $hasher;

    /**
     * @param Mailer $mail
     * @param Hasher $hasher
     * @param SendResetToken $sendResetToken
     * @return void
     */
    public function __construct(
        Mailer $mail,
        Hasher $hasher,
        protected SendResetToken $sendResetToken
    ) {
        $this->mail = $mail;
        $this->hasher = $hasher;
    }

    // Activate the user's email account using the verification token.
    /**
     * @param array $validated
     * @return ServiceResponse
     */
    public function activateAccount(array $validated): ServiceResponse
    {
        try {
            // Find the user by verification token, throws exception if not found
            $user = User::where('email_verification_token', $validated['token'])->firstOrFail();

            // Update user email verification details
            $user->email_verified_at = now();
            $user->email_verification_token = null;
            $user->save();

            // Return a success message
            return new ServiceResponse(
                message: 'Email verified successfully'
            );
        } catch (ModelNotFoundException $e) {
            // Handle case when user is not found with the given token
            return new ServiceResponse(
                error: 'Invalid verification token'
            );
        }
    }

    // Authenticate a user and generate a JWT.
    /**
     * @param array $credentials
     * @return ServiceResponse
     */
    public function authenticateUser(array $validated): ServiceResponse
    {
        if (!$token = Auth::guard('api')->attempt($validated)) {
            return new ServiceResponse(
                error: 'Invalid email or password',
                status: 401
            );
        }

        // Get the authenticated user
        $user = Auth::guard('api')->user();

        // Check if email is verified
        if (!$user->email_verified_at) {
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
     * @param array $validated
     * @return ServiceResponse
     */
    public function sendResetToken(array $validated): ServiceResponse
    {
        return $this->sendResetToken->execute($validated);
    }

    // Resets the user's password using a provided reset token.
    /**
     * @param array $validated
     * @return ServiceResponse
     */
    public function resetPasswordWithToken(array $validated): ServiceResponse
    {
        $response = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $this->hasher->make($password)
                ])->save();
                Cache::forget('user:me:' . $user->id);
            }
        );

        if ($response === Password::PASSWORD_RESET) {
            return new ServiceResponse(
                message: 'Password reset successfully.',
                status: 200
            );
        }

        return new ServiceResponse(
            error: 'The reset token is invalid or has expired.',
            status: 401
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
    // CACHE-RELATED
    // ----------
    // Retrieves user data from the cache.
    /**
     * @param ServiceResponse $result
     * @return ServiceResponse|null
     */
    public function getUserFromCache(ServiceResponse $result): ServiceResponse|null
    {
        // Check if 'user' key exists in the data array
        if (!isset($result->data['user'])) {
            return null; // Return null if user data is missing
        }

        $userId = $result->data['user']->id;
        $cacheKey = 'user:me:' . $userId;
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData || !isset($cachedData['data'], $cachedData['message'])) {
            return null; // Return null if cache data is missing or malformed (missing 'data' or 'message')
        }

        // Wrap cached data in a ServiceResponse
        return new ServiceResponse(
            data: $cachedData['data'],
            message: $cachedData['message']
        );
    }

    // Stores user data in the cache.
    /**
     * @param ServiceResponse $result
     * @param int $cacheTime
     * @return void
     */
    public function storeUserInCache(ServiceResponse $result, $cacheTime = 900)
    {
        if (!isset($result->data['user'])) {
            return; // Return early if user data is missing
        }

        $userId = $result->data['user']->id;
        $cacheKey = 'user:me:' . $userId;
        $cacheData = [
            'data' => $result->data,
            'message' => $result->message,
        ];
        Cache::put($cacheKey, $cacheData, $cacheTime);
    }

    // Removes the user data from the cache.
    /**
     * @param ServiceResponse $result
     * @return void
     */
    public function forgetUserFromCache(ServiceResponse $result)
    {
        if (!isset($result->data['user'])) {
            return; // Return early if user data is missing
        }

        $userId = $result->data['user']->id;
        $cacheKey = 'user:me:' . $userId;
        dd(env("CACHE_STORE"));
        Cache::forget($cacheKey);
    }
}
