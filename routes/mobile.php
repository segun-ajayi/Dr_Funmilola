<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileMutationController;
use App\Http\Controllers\Mobile\MobileOperationsController;
use App\Http\Controllers\Mobile\MobilePatientController;
use Illuminate\Support\Facades\Route;

Route::middleware('mobile.envelope')->group(function () {
    Route::get('/capabilities', [MobilePatientController::class, 'capabilities']);
    Route::post('/auth/token', [MobileAuthController::class, 'store'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'active', 'verified', 'abilities:mobile:v1', 'role:patient'])->group(function () {
        Route::delete('/auth/token', [MobileAuthController::class, 'destroy']);
        Route::get('/me', [MobilePatientController::class, 'me']);
        Route::patch('/me', [MobileOperationsController::class, 'updateProfile'])->middleware('throttle:20,1');
        Route::get('/appointments', [MobilePatientController::class, 'appointments']);
        Route::get('/appointments/{appointment}/reschedule-options', [MobileMutationController::class, 'rescheduleOptions'])->middleware('throttle:60,1');
        Route::post('/appointments/{appointment}/cancellation-requests', [MobileMutationController::class, 'cancellation'])->middleware('throttle:20,1');
        Route::post('/appointments/{appointment}/reschedule-requests', [MobileMutationController::class, 'reschedule'])->middleware('throttle:20,1');
        Route::get('/documents', [MobilePatientController::class, 'documents']);
        Route::post('/documents', [MobileOperationsController::class, 'uploadDocument'])->middleware('throttle:10,1');
        Route::get('/documents/{document}/download', [MobileOperationsController::class, 'downloadDocument'])->middleware('throttle:30,1');
        Route::get('/message-threads', [MobilePatientController::class, 'threads']);
        Route::post('/message-threads', [MobileOperationsController::class, 'createThread'])->middleware('throttle:20,1');
        Route::post('/message-threads/{thread}/messages', [MobileOperationsController::class, 'reply'])->middleware('throttle:30,1');
        Route::get('/notifications', [MobilePatientController::class, 'notifications']);
        Route::patch('/notifications/{id}/read', [MobileOperationsController::class, 'readNotification'])->middleware('throttle:60,1');
        Route::get('/notification-preferences', [MobileOperationsController::class, 'preferences']);
        Route::put('/notification-preferences', [MobileOperationsController::class, 'updatePreferences'])->middleware('throttle:20,1');
        Route::get('/devices', [DeviceController::class, 'index']);
        Route::delete('/devices/{token}', [DeviceController::class, 'destroy'])->middleware('throttle:20,1');
        Route::post('/devices/push-token', [MobilePatientController::class, 'registerPush']);
        Route::get('/consultations', [MobilePatientController::class, 'consultations']);
        Route::get('/consultations/{consultation}', [MobileOperationsController::class, 'consultation']);
        Route::post('/consultations/{consultation}/consent', [MobileOperationsController::class, 'consent']);
        Route::post('/consultations/{consultation}/waiting-room', [MobileOperationsController::class, 'wait']);
        Route::post('/consultations/{consultation}/join', [MobileOperationsController::class, 'join'])->middleware('throttle:20,1');
        Route::post('/consultations/{consultation}/leave', [MobileOperationsController::class, 'leave']);
    });
});
