<?php
use App\Http\Controllers\Mobile\MobileAuthController;use App\Http\Controllers\Mobile\MobileMutationController;use App\Http\Controllers\Mobile\MobilePatientController;use Illuminate\Support\Facades\Route;
Route::middleware('mobile.envelope')->group(function(){
 Route::get('/capabilities',[MobilePatientController::class,'capabilities']);
 Route::post('/auth/token',[MobileAuthController::class,'store'])->middleware('throttle:5,1');
 Route::middleware(['auth:sanctum','active','verified','abilities:mobile:v1','role:patient'])->group(function(){
  Route::delete('/auth/token',[MobileAuthController::class,'destroy']);Route::get('/me',[MobilePatientController::class,'me']);Route::get('/appointments',[MobilePatientController::class,'appointments']);Route::get('/documents',[MobilePatientController::class,'documents']);Route::get('/message-threads',[MobilePatientController::class,'threads']);Route::get('/notifications',[MobilePatientController::class,'notifications']);Route::get('/consultations',[MobilePatientController::class,'consultations']);Route::post('/appointments/{appointment}/cancellation-requests',[MobileMutationController::class,'cancellation'])->middleware('throttle:20,1');Route::post('/devices/push-token',[MobilePatientController::class,'registerPush']);
 });
});
