<?php

namespace App\Http\Controllers;

use App\Http\Requests\{
    ActivateRequest,
    LoginRequest,
    RegisterUserRequest,
    ResetPasswordRequest,
    SendResetTokenRequest,
};
use App\Helpers\ApiResponse;
use App\Services\AuthService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;

class AuthController extends Controller
{
    protected $authService;

    /**
     * @param AuthService $authService
     * @return void
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware(
            'auth:api',
            ['except' => [
                'login',
                'forgotPassword',
                'resetPassword',
                'activate',
                'ok'
            ]]
        );
    }

    // Activate the user's email account using the verification token.
    /**
     * @param ActivateRequest $request
     * @return JsonResponse
     */
    public function activate(ActivateRequest $request)
    {
        $result = $this->authService->activateAccount($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    // Login a user and issue a JWT.
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        dd($request->all());
        $result = $this->authService->authenticateUser($request->validated());

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

    // Reset password using token.
    /**
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = $this->authService->resetPasswordWithToken($request->validated());
        return ApiResponse::fromServiceResult($result);
    }

    // Get a new token without re-entering credentials
    /**
     * @return JsonResponse
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

    // Refreshes the JSON Web Token (JWT) for the authenticated user.
    /**
     * @return JsonResponse
     */
    public function refreshJWT()
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

    // Logout the authenticated user.
    /**
     * @return JsonResponse
     */
    public function logout()
    {
        $result = $this->authService->getAuthenticatedUser();
        $this->authService->forgetUserFromCache($result);
        $result = $this->authService->logoutUser();

        return ApiResponse::fromServiceResult($result);
    }

    // Get details of the authenticated user.
    /**
     * @return JsonResponse
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
