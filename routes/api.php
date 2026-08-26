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
use App\Http\Controllers\Staff\AppointmentController as StaffAppointmentController;
use App\Http\Controllers\Staff\AvailabilityRuleController;
use App\Http\Controllers\Staff\CalendarController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\PatientSearchController;
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
    Route::prefix('staff')->middleware('role:admin,moderator,power_admin')->group(function () {
        Route::get('/dashboard', DashboardController::class);
        Route::get('/patients/search', PatientSearchController::class)->middleware('throttle:60,1');
        Route::get('/appointments', [StaffAppointmentController::class, 'index']);
        Route::patch('/appointments/{appointment}/status', [StaffAppointmentController::class, 'updateStatus']);
        Route::patch('/appointments/{appointment}/reschedule', [StaffAppointmentController::class, 'reschedule']);
        Route::get('/calendar', CalendarController::class);
        Route::get('/availability-rules', [AvailabilityRuleController::class, 'index']);
        Route::post('/availability-rules', [AvailabilityRuleController::class, 'store']);
        Route::put('/availability-rules/{availabilityRule}', [AvailabilityRuleController::class, 'update']);
    });
});
