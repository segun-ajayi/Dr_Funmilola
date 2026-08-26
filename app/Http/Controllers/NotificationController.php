<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse { return response()->json(['data'=>$request->user()->notifications()->latest()->limit(30)->get(),'unread'=>$request->user()->unreadNotifications()->count()]); }
    public function read(Request $request, string $id): JsonResponse { $notification=$request->user()->notifications()->findOrFail($id);$notification->markAsRead();return response()->json(['message'=>'Notification marked as read.']); }
}
