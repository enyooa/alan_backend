<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use Twilio\Rest\Client; // <--

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

        return response()->json(['message' => 'Не правильный логин или пароль, либо этот пользователь не зарегистрирован!'], 401);
    }

    /**
     * Register a new user and send a Twilio verification code.
     */
    public function register(Request $request)
    {
        Log::info('Register Request', $request->all());

        try {
            // 1. Validate input
            $fields = $request->validate([
                "first_name"       => 'required|string|max:255',
                "last_name"        => 'nullable|string|max:255',
                "surname"          => 'nullable|string|max:255',
                "whatsapp_number"  => 'required|string|unique:users|max:15',
                "password"         => 'required|string|confirmed|min:8',
            ]);

            // 2. Create the user
            $user = User::create([
                'first_name'      => $fields['first_name'],
                'last_name'       => $fields['last_name'] ?? '',
                'surname'         => $fields['surname'] ?? '',
                'whatsapp_number' => $fields['whatsapp_number'],
                'password'        => Hash::make($fields['password']),
            ]);

            // 3. Assign the 'client' role, or any default role
            $clientRole = Role::where('name', 'client')->first();
            if (!$clientRole) {
                return response()->json([
                    'message' => 'Default "client" role not found. Please create it first.'
                ], 500);
            }
            $user->roles()->attach($clientRole);

            // 4. Generate a token (if you need to return it to the client)
            $token = $user->createToken('user-auth-token')->plainTextToken;
            $roles = $user->roles()->pluck('name')->toArray();

            Log::info('Register Response Payload', [
                'id' => $user->id,
                'user' => [
                    'first_name'      => $user->first_name,
                    'last_name'       => $user->last_name,
                    'surname'         => $user->surname,
                    'whatsapp_number' => $user->whatsapp_number,
                    'roles'           => $roles,
                ],
                'token' => $token,
                'message' => 'User registered and verification code sent!',
            ]);

            // 5. Prepare phone number for Twilio (E.164 format)
            $phoneNumber = $user->whatsapp_number;
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+'.$phoneNumber;
            }

            // 6. Send Twilio Verification (SMS or WhatsApp)
            $twilio = new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );

            $verification = $twilio->verify->v2->services(config('services.twilio.verify_sid'))
                ->verifications
                ->create($phoneNumber, "sms");
                // If you want WhatsApp:
                // ->create("whatsapp:$phoneNumber", "whatsapp");

            Log::info("Twilio verification sent", ['sid' => $verification->sid]);

            // 7. Return success response
            return response()->json([
                'id'    => $user->id,
                'user'  => [
                    'first_name'      => $user->first_name,
                    'last_name'       => $user->last_name,
                    'surname'         => $user->surname,
                    'whatsapp_number' => $user->whatsapp_number,
                    'roles'           => $roles,
                ],
                'token'   => $token,
                'message' => 'User registered and verification code sent!',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration.',
                'error'   => $e->getMessage(),
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

    /**
     * Create an account with custom role
     */
    public function createAccount(Request $request)
    {
        Log::info($request->all());
        // Validate the request inputs
        $request->validate([
            'firstName' => 'required|string',
            'lastName'  => 'nullable|string',
            'whatsappNumber' => 'required|string',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
        ]);

        // Create the user
        $user = User::create([
            'first_name'      => $request->firstName,
            'last_name'       => $request->lastName,
            'whatsapp_number' => $request->whatsappNumber,
            'password'        => Hash::make($request->password),
        ]);

        // Optionally assign role
        $user->assignRole($request->role);

        return response()->json(['message' => 'Account successfully created'], 201);
    }

    /**
     * Verify phone using Twilio
     */
    public function verifyPhone(Request $request)
    {
        // 1. Validate input
        $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string',
        ]);

        try {
            // 2. Create Twilio client
            $twilio = new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );

            // 3. Call Twilio Verify -> verificationChecks
            $verificationCheck = $twilio->verify->v2->services(config('services.twilio.verify_sid'))
                ->verificationChecks
                ->create([
                    'to'   => $request->phone,
                    'code' => $request->code,
                ]);

            // 4. Check status
            if ($verificationCheck->status === 'approved') {
                // (Optional) Mark user as verified in DB if desired
                // $user = User::where('whatsapp_number', ltrim($request->phone, '+'))->first();
                // $user->update(['phone_verified_at' => now()]);

                return response()->json([
                    'message' => 'Verification successful!',
                    'status'  => $verificationCheck->status
                ], 200);
            } else {
                // If not approved, code is invalid or expired
                return response()->json([
                    'message' => 'Invalid or expired code',
                    'status'  => $verificationCheck->status
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error("Error verifying phone: " . $e->getMessage());
            return response()->json([
                'message' => 'Error verifying code',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
