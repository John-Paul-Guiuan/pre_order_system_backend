<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        // 🪶 Debugging: log everything Laravel receives
        Log::info('📦 Incoming profile update:', [
            'all' => $request->all(),
            'files' => $request->file(),
            'headers' => $request->headers->all(),
        ]);

        $user = $request->user();

        // ✅ Validation (only when fields are present)
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        // 🖼️ Handle profile image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('profile_images', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        }

        // ✏️ Update user info
        $user->fill(array_intersect_key($validated, array_flip(['name', 'phone', 'address', 'image_url'])));
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => $user->fresh(),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        if (!Hash::check($validated['old_password'], $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }
}
