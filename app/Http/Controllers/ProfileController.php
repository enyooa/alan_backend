<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

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
    public function getProfile(Request $request): JsonResponse
{
    /** @var \App\Models\User $user */
    $user = $request->user()->loadMissing([
        'roles:id,name',
        'permissions:id,code,name',
        'roles.permissions:id,code,name',
        'organization:id,name',
        'organization.plans.permissions:id,code,name',
    ]);

    /* ─── подгружаем недостающие поля для всех прав, которые "сидят" в кэше Spatie ─── */
    $user->loadMissing('permissions');               // ← при повторном вызове не грузит заново
    $user->roles->each(fn ($r) => $r->loadMissing('permissions:id,code,name'));

    return response()->json([
        'id'              => $user->id,
        'is_verified'     => !is_null($user->phone_verified_at),
        'roles'           => $user->roles->pluck('name')->values(),
        'organization'    => $this->normalizeOrganization($user->organization),
        'first_name'      => $user->first_name,
        'last_name'       => $user->last_name,
        'surname'         => $user->surname,
        'whatsapp_number' => $user->whatsapp_number,
        'photo'           => $user->photo ? asset('storage/'.$user->photo) : null,

        /* ↓↓↓ итоговый список без дублей и с гарантированно заполненными полями */
        'permissions'     => $user->allPermissions()
                                  ->map(fn ($p) => [
                                      'id'   => $p->id,
                                      'code' => (int) $p->code,
                                      'name' => $p->name,
                                  ])
                                  ->unique('id')
                                  ->sortBy('code')
                                  ->values(),
    ], 200);
}

/* необязательно, но удобно убрать pivot-ы у plan-ов внутри organization */
private function normalizeOrganization(?Organization $org): ?array
{
    if (!$org) return null;

    $org->plans->each(function ($plan) {
        $plan->makeHidden('pivot');
        $plan->permissions->makeHidden('pivot');
    });

    return $org->toArray();
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
