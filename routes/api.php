<?php

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | This file defines API routes for the application.
    | - `apiResource()` is used for standard CRUD operations:
        GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
        apiResource handles all these CRUD routes
    | - Custom routes are added first in their corresponding resource.
    | - Routes are grouped by middleware and functionality.
    |
*/

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\{
    AuthController,
    BookingController,
    ProviderController,
    ProviderWorkingHourController,
    ServiceController,
    UserController
};

$publicApiMiddleware = ['api'];

// =======================
// PUBLIC API ROUTES
// =======================
Route::group(['middleware' => $publicApiMiddleware], function () {
    Route::get('/', function () {
        try {
            Mail::raw('This is a test email', function ($message) {
                $message->to('jonas.sorensen.93dk@gmx.com')
                    ->subject('Test Email from Booohking :)');
            });

            // If no exception, email was sent
            echo 'Email sent successfully!';
        } catch (\Exception $e) {
            // Catch any error (SMTP connection issues, authentication, etc.)
            echo 'Failed to send email. Error: ' . $e->getMessage();
        }
    });

    // ---- AuthController Routes ----
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('register', 'register')->name('auth.register');
        Route::post('activate-account', 'activateAccount');
        Route::post('login', 'login')->name('login')->middleware('throttle:login');
        Route::post('forgot-password', 'forgotPassword')->middleware('throttle:password-reset');
        Route::post('reset-password', 'resetPassword')->middleware('throttle:password-reset');
        Route::post('clone-token', 'cloneToken')->middleware('throttle:jwt');
        Route::post('logout', 'logout')->name('auth.logout');
        Route::get('me', 'me')->middleware('auth:api')->name('auth.me');
        Route::get('refreshJWT', 'refreshJWT')->middleware(['auth:api', 'throttle:jwt']);
    });

    // ---- UserController Routes ----
    Route::apiResource('users', UserController::class);

    // ---- BookingController Routes ----
    Route::prefix('bookings')->controller(BookingController::class)->group(function () {
        Route::get('{provider}/available-slots', 'availableSlots');
        Route::get('users/{user}', 'readBookingsByUserID');
    });
    Route::apiResource('bookings', BookingController::class);

    // ---- ProviderController ----
    Route::prefix('providers')->controller(ProviderController::class)->group(function () {
        Route::get('services/{service}', 'readProvidersByServiceID');
    });
    Route::apiResource('providers', ProviderController::class);

    // ---- ProviderWorkingHourController ----
    Route::apiResource('provider-working-hours', ProviderWorkingHourController::class);

    // ---- ServiceController ----
    Route::prefix('services')->controller(ServiceController::class)->group(function () {
        Route::get('users/{user}', 'readServicesByUserId');
    });
    Route::apiResource('services', ServiceController::class);
});
