<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ActivateRequest;
use App\Http\Requests\SendResetTokenRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('auth:api', ['except' => ['login', 'forgotPassword', 'resetPassword', 'register', 'activate', 'ok']]);
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $result = $this->authService->registerUser($request->all());
        return ApiResponse::fromServiceResult($result);
    }

    public function activate(ActivateRequest $request)
    {
        $result = $this->authService->activateAccount($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    /**
     * Login a user and issue a JWT.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['User_Email', 'password']);
        $result = $this->authService->authenticateUser($credentials);

        if (!$result->error) {
            $this->authService->forgetUserFromCache($result);
        }

        return ApiResponse::fromServiceResult($result);
    }

    // Send password reset token.
    /**
     * @param SendResetTokenRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(SendResetTokenRequest $request)
    {
        $result = $this->authService->sendResetToken($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request)
    {
        $result = $this->authService->resetPasswordWithToken($request->all());
        return ApiResponse::fromServiceResult($result);
    }

    /**
     * This is useful for mobile apps to get a new token without re-entering credentials
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cloneToken()
    {
        try {
            $result = $this->authService->cloneToken();
            return ApiResponse::fromServiceResult($result);
        } catch (\Throwable $e) {
            $meta = config('app.debug')
                ? ['exception' => $e->getMessage()]
                : null;

            return ApiResponse::error('Failed to generate token', 401, $meta); // HTTP 401 Unauthorized
        }
    }

    /**
     * Refreshes the JSON Web Token (JWT) for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request instance.
     * @return \Illuminate\Http\JsonResponse JSON response containing the new access token or an error message.
     */
    public function refreshJWT(Request $request)
    {
        try {
            $result = $this->authService->refreshJWT();
            return ApiResponse::fromServiceResult($result);
        } catch (\Throwable $e) {
            $meta = config('app.debug')
                ? ['exception' => $e->getMessage()]
                : null;

            return ApiResponse::error('Failed to generate token', 401, $meta); // HTTP 401 Unauthorized
        }
    }

    /**
     * Logout the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $result = $this->authService->getAuthenticatedUser();
        $this->authService->forgetUserFromCache($result);
        $result = $this->authService->logoutUser();

        return ApiResponse::fromServiceResult($result);
    }

    /**
     * Get details of the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $result = $this->authService->getAuthenticatedUser();

        if ($result->error || $result->errors) {
            return ApiResponse::fromServiceResult($result);
        }

        // Check if the user is cached
        $cachedData = $this->authService->getUserFromCache($result);

        if ($cachedData) {
            return ApiResponse::fromServiceResult($cachedData);
        }

        // Cache user for 15 minutes
        $this->authService->storeUserInCache($result);

        return ApiResponse::fromServiceResult($result);
    }
}
