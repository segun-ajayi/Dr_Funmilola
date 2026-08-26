<?php

use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\PublicContentController;
use Illuminate\Support\Facades\Route;

Route::get('/public', PublicContentController::class);
Route::get('/availability/{service}', AvailabilityController::class)->middleware('throttle:60,1');
Route::post('/appointment-requests', AppointmentRequestController::class)->middleware('throttle:10,1');
