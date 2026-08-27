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
use App\Http\Controllers\PatientProfileController;
use App\Http\Controllers\PatientDocumentController;
use App\Http\Controllers\MessageThreadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\CancellationRequestController;
use App\Http\Controllers\MyAppointmentController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\Staff\AppointmentController as StaffAppointmentController;
use App\Http\Controllers\Staff\AvailabilityRuleController;
use App\Http\Controllers\Staff\CalendarController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\PatientSearchController;
use App\Http\Controllers\Staff\InboxController;
use App\Http\Controllers\Staff\AvailabilityExceptionController;
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
        Route::get('/documents/{document}/download', [PatientDocumentController::class, 'download']);
        Route::get('/me/notifications', [NotificationController::class, 'index']);
        Route::patch('/me/notifications/{id}/read', [NotificationController::class, 'read']);
        Route::middleware('role:patient')->group(function () {
            Route::post('/me/appointments/{appointment}/cancellation-request', [CancellationRequestController::class, 'store']);
            Route::get('/me/profile', [PatientProfileController::class, 'show']);
            Route::put('/me/profile', [PatientProfileController::class, 'update']);
            Route::get('/me/documents', [PatientDocumentController::class, 'index']);
            Route::post('/me/documents', [PatientDocumentController::class, 'store'])->middleware('throttle:10,1');
            Route::get('/me/message-threads', [MessageThreadController::class, 'index']);
            Route::post('/me/message-threads', [MessageThreadController::class, 'store'])->middleware('throttle:20,1');
            Route::post('/me/message-threads/{thread}/messages', [MessageThreadController::class, 'reply'])->middleware('throttle:30,1');
            Route::get('/me/notification-preferences', [NotificationPreferenceController::class, 'show']);
            Route::put('/me/notification-preferences', [NotificationPreferenceController::class, 'update']);
        });
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
        Route::get('/availability-exceptions', [AvailabilityExceptionController::class, 'index']);
        Route::post('/availability-exceptions', [AvailabilityExceptionController::class, 'store']);
        Route::delete('/availability-exceptions/{availabilityException}', [AvailabilityExceptionController::class, 'destroy']);
        Route::get('/inbox', [InboxController::class, 'index']);
        Route::post('/message-threads/{thread}/messages', [InboxController::class, 'reply']);
        Route::patch('/cancellation-requests/{cancellation}', [InboxController::class, 'reviewCancellation']);
        Route::get('/patients/{patient}', [InboxController::class, 'patient']);
    });
});
