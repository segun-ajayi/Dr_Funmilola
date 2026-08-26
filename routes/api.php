<?php

use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MobileTokenController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\MyAppointmentController;
use App\Http\Controllers\PublicContentController;
use Illuminate\Support\Facades\Route;

Route::get('/public', PublicContentController::class);
Route::get('/availability/{service}', AvailabilityController::class)->middleware('throttle:60,1');
Route::post('/appointment-requests', AppointmentRequestController::class)->middleware('throttle:10,1');

Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterController::class)->middleware('throttle:5,1');
    Route::post('/login', LoginController::class)->middleware('throttle:5,1');
    Route::post('/forgot-password', ForgotPasswordController::class)->middleware('throttle:3,1');
    Route::post('/reset-password', ResetPasswordController::class)->middleware('throttle:5,1');
    Route::post('/mobile-token', [MobileTokenController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/me', MeController::class);
    Route::post('/auth/logout', LogoutController::class);
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])->middleware('throttle:3,1');
    Route::middleware('verified')->group(function () {
        Route::get('/me/appointments', [MyAppointmentController::class, 'index']);
        Route::get('/me/appointments/{appointment}', [MyAppointmentController::class, 'show']);
    });
    Route::get('/staff/ping', fn () => response()->json(['message' => 'Staff access confirmed.']))->middleware('role:admin,moderator,power_admin');
});
