<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Upload Profile Photo
     */
    public function uploadPhoto(Request $request)
    {
        Log::info($request->all());

        try {
            // Validate the uploaded file
            $request->validate([
                'photo' => 'required|image|mimes:jpg,jpeg,png', // Allow JPG, JPEG, PNG
            ]);

            $user = Auth::user();

            // Delete existing photo if it exists
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            // Store the new photo in the 'photos' directory within the public disk
            $path = $request->file('photo')->store(env('PHOTO_DIRECTORY', 'photos'), 'public');

            // Save the photo path to the user record
            $user->photo = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'photo'   => asset('storage/' . $path),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response
            \Log::error('Error uploading photo: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo.',
            ], 500);
        }
    }

    /**
     * Get User Profile Information
     */
    public function getProfile()
    {
        try {
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'user' => [
                    'id'              => $user->id,
                    'first_name'      => $user->first_name,
                    'last_name'       => $user->last_name,
                    'surname'         => $user->surname,
                    'whatsapp_number' => $user->whatsapp_number,
                    'summary'         => $user->summary,
                    'address'         => $user->address,
                    'photo'           => $user->photo ? asset('storage/' . $user->photo) : asset('images/default_user.png'),
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile information.',
            ], 500);
        }
    }

    /**
     * Update User Profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $validatedData = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name'  => 'nullable|string|max:255',
                'email'      => 'nullable|email|max:255', // Add validation for email if needed
            ]);

            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error updating profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
            ], 500);
        }
    }
}
