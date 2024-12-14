<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Handle user login and return user details with token.
     */
    public function login(Request $request)
    {
        // Log::info('Login attempt:', $request->all());

        $request->validate([
            'whatsapp_number' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt(['whatsapp_number' => $request->whatsapp_number, 'password' => $request->password])) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Fetch the roles of the user
            $roles = $user->roles()->pluck('name')->toArray();

            return response()->json([
                'id' => $user->id, // Include user ID
                'token' => $token,
                'roles' => $roles,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'surname' => $user->surname,
                'whatsapp_number' => $user->whatsapp_number,
                'photo' => $user->photo ? asset('storage/' . $user->photo) : null,
            ], 200);
        }

        return response()->json(['message' => 'Incorrect login or password'], 401);
    }

    public function register(Request $request){
        Log::info('Register Request', $request->all());

        try {
            // Validate the incoming request
            $fields = $request->validate([
                "first_name" => 'required|string|max:255',
                "last_name" => 'string|max:255',
                "surname" => 'string|max:255',
                "whatsapp_number" => 'required|string|unique:users|max:15',
                "password" => 'required|string|confirmed|min:8',
            ]);
    
            // Create the user
            $user = User::create([
                'first_name' => $fields['first_name'],
                'last_name' => $fields['last_name'],
                'surname' => $fields['surname'],
                'whatsapp_number' => $fields['whatsapp_number'],
                'password' => Hash::make($fields['password']), // Use Hash::make for password hashing
            ]);
    
            // Fetch the default "client" role
            $clientRole = Role::where('name', 'client')->first();
            if (!$clientRole) {
                return response()->json([
                    'message' => 'Default "client" role not found. Please create it first.'
                ], 500);
            }
    
            // Assign the default "client" role to the user
            $user->roles()->attach($clientRole);
    
            // Fetch the user's roles
            $roles = $user->roles()->pluck('name')->toArray();
    
            // Generate a token for the user
            $token = $user->createToken('user-auth-token')->plainTextToken;
    
            // Prepare the response
            return response()->json([
                'id' => $user->id,
                'user' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'surname' => $user->surname,
                    'whatsapp_number' => $user->whatsapp_number,
                    'roles' => $roles,
                ],
                'token' => $token,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    /**
     * Handle photo upload for authenticated user.
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'image|mimes:jpg,jpeg,png',
        ]);

        $user = auth()->user();

        // Delete existing photo if any
        if ($user->photo) {
            \Storage::delete('public/' . $user->photo);
        }

        // Store new photo
        $path = $request->file('photo')->store('photos', 'public');
        $user->photo = $path;
        $user->save();

        return response()->json(['photo' => asset('storage/' . $path)], 200);
    }

    /**
     * Log out the authenticated user and revoke tokens.
     */
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return response(['message' => 'Logged out'], 201);
    }
}
