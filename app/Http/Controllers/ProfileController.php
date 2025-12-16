<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        \Log::info('Profile update raw request', [
            'all' => $request->all(),
            'name' => $request->input('name'),
            'hasFile' => $request->hasFile('image'),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method(),
        ]);

        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            \Log::info('Profile update request', [
                'user_id' => $user->id,
                'request_method' => $request->method(),
                'request_data' => $request->except(['image']),
                'has_file' => $request->hasFile('image'),
            ]);

            // Validate input
            $validated = $request->validate([
                'name' => 'required|string|min:1|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            \Log::info('Validation passed', ['user_id' => $user->id]);

            // Handle image upload
            $imageUrl = $user->image_url;
            
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Delete old image if exists
                if ($user->image_url && strpos($user->image_url, 'storage/') === 0) {
                    $oldPath = str_replace('storage/profiles/', '', $user->image_url);
                    Storage::disk('public')->delete('profiles/' . $oldPath);
                    \Log::info('Old image deleted', ['path' => $oldPath]);
                }

                // Store new image
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('profiles', $filename, 'public');
                $imageUrl = 'storage/profiles/' . $filename;

                \Log::info('Profile image uploaded', [
                    'user_id' => $user->id,
                    'filename' => $filename,
                    'path' => $path,
                ]);
            }

            // Update user with validated data
            $user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? $user->phone,
                'address' => $validated['address'] ?? $user->address,
                'image_url' => $imageUrl,
            ]);

            \Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated),
            ]);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user->fresh(),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Profile validation error', [
                'user_id' => auth()->id(),
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Profile update error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $request->validate([
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:6',
            ]);

            // Verify old password
            if (!\Illuminate\Support\Facades\Hash::check($request->old_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 422);
            }

            // Update password
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
            ]);

            return response()->json(['message' => 'Password changed successfully'], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Password change error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to change password'], 500);
        }
    }
}
