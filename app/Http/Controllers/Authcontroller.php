<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
                "last_name" => 'nullable|string|max:255',
                "surname" => 'nullable|string|max:255',
                "whatsapp_number" => 'required|string|unique:users|max:15',
                "password" => 'required|string|confirmed|min:8',
            ]);
    
            // Create the user
            $user = User::create([
                'first_name' => $fields['first_name'],
                'last_name' => $fields['last_name'] ?? '', // Default to empty string
                'surname' => $fields['surname'] ?? '',  
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
     * Log out the authenticated user and revoke tokens.
     */
    public function logout(Request $request)
{
    try {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $user->tokens()->delete(); // Delete all tokens
        return response()->json(['message' => 'Logged out successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Server error', 'details' => $e->getMessage()], 500);
    }
}

public function createAccount(Request $request)
    {
        Log::info($request->all());
        // Validate the request inputs
        $request->validate([
            'firstName' => 'required|string',
            'lastName' => 'nullable|string',
            'whatsappNumber' => 'required|string',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            
        ]);

        
        // Create the user
        $user = User::create([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'whatsapp_number' => $request->whatsappNumber,
            'password' => Hash::make($request->password), // Encrypt the password
        ]);

        // Optionally assign role
        $user->assignRole($request->role);

        return response()->json(['message' => 'Account successfully created'], 201);
    }

}
