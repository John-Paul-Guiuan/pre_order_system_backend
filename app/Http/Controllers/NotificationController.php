<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // ✅ Add this line

class NotificationController extends Controller
{
    // 🔔 Get all notifications for a user
    public function index($user_id)
    {
        $user = User::findOrFail($user_id);
        return response()->json($user->notifications);
    }

    // ✅ Mark a notification as read
    public function markAsRead($id)
    {
        $notification = \Illuminate\Notifications\DatabaseNotification::find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

}
