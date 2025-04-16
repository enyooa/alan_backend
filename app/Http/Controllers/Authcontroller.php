<?php

namespace App\Http\Controllers;

use App\Models\PhoneVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Http;
use Twilio\Rest\Client; // <--

use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login and return user details with token.
     */
    public function login(Request $request)
    {
        Log::info('Login attempt:', $request->all());

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
            // Validate input
            $fields = $request->validate([
                "first_name"      => 'required|string|max:255',
                "last_name"       => 'nullable|string|max:255',
                "surname"         => 'nullable|string|max:255',
                "whatsapp_number" => 'required|string|unique:users|max:15',
                "password"        => 'required|string|confirmed|min:8',
            ]);

            // Format the phone number (always convert to e.g. "7076069831")
            $formattedNumber = $this->formatPhoneNumber($fields['whatsapp_number']);
            $fields['whatsapp_number'] = $formattedNumber;

            // Create the user
            $user = User::create([
                'first_name'      => $fields['first_name'],
                'last_name'       => $fields['last_name'] ?? '',
                'surname'         => $fields['surname'] ?? '',
                'whatsapp_number' => $formattedNumber,
                'password'        => Hash::make($fields['password']),
            ]);

            // Assign 'client' role
            $clientRole = Role::where('name', 'client')->first();
            if (!$clientRole) {
                return response()->json([
                    'message' => 'Default "client" role not found.'
                ], 500);
            }
            $user->roles()->attach($clientRole);

            // Generate token
            $token = $user->createToken('user-auth-token')->plainTextToken;
            $roles = $user->roles()->pluck('name')->toArray();

            Log::info('Register Response Payload', [
                'id'   => $user->id,
                'user' => [
                    'first_name'      => $user->first_name,
                    'last_name'       => $user->last_name,
                    'surname'         => $user->surname,
                    'whatsapp_number' => $user->whatsapp_number,
                    'roles'           => $roles,
                ],
                'token'   => $token,
                'message' => 'User registered!',
            ]);

            // Check WhatsApp via GreenAPI
            $hasWhatsapp = $this->checkWhatsappGreenApi($formattedNumber);
            if (!$hasWhatsapp) {
                Log::info("User does NOT have WhatsApp. Possibly send SMS instead...");
            } else {
                Log::info("User HAS WhatsApp. Maybe send them a WhatsApp message...");
            }

            // Trigger sending a verification code now
            $this->sendVerificationCode($formattedNumber);

            return response()->json([
                'id'           => $user->id,
                'user'         => [
                    'first_name'      => $user->first_name,
                    'last_name'       => $user->last_name,
                    'surname'         => $user->surname,
                    'whatsapp_number' => $user->whatsapp_number,
                    'roles'           => $roles,
                ],
                'token'        => $token,
                'has_whatsapp' => $hasWhatsapp,
                'message'      => 'User registered! Verification code sent.',
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
     * Generate a random 4-digit code, store it in PhoneVerification table,
     * and send it via GreenAPI.
     */
    public function sendVerificationCode(String $request)
{
    // Extract and format the phone number from the request.
    $phoneNumber = $this->formatPhoneNumber($request);

    // Generate a 4-digit random code.
    $code = rand(1000, 9999);

    // Store or update the verification record.
    PhoneVerification::updateOrCreate(
        ['phone_number' => $phoneNumber],
        ['code' => $code]
    );

    // Prepare GreenAPI call.
    $chatId = $phoneNumber . '@c.us';
    $url = 'https://7105.api.greenapi.com/waInstance7105215666/sendMessage/96df68496897444f89ec3dc7b044d4f45b1a0365634f4ab2ba';

    $payload = [
        'chatId'  => $chatId,
        'message' => "Ваш код: $code",
    ];

    // Send via HTTP.
    Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post($url, $payload);
}

    /**
     * Verify the code entered by the user.
     */
    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'code'         => 'required|string',
        ]);

        // Format the phone number consistently.
        $phone = $this->formatPhoneNumber($validated['phone_number']);

        // Check if the code exists for this phone number.
        $verification = PhoneVerification::where('phone_number', $phone)
            ->where('code', $validated['code'])
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Неверный код'], 400);
        }

        // Remove the verification record.
        $verification->delete();

        // Mark the user as verified by setting phone_verified_at.
        $user = User::where('whatsapp_number', $phone)->first();
        if ($user) {
            $user->phone_verified_at = now();
            $user->save();
        }

        return response()->json(['message' => 'Успешно подтверждено!']);
    }

    /**
     * Check if the given phone number is registered with WhatsApp using GreenAPI.
     */
    private function checkWhatsappGreenApi($phoneNumber)
    {
        try {
            // Karla
            $url = "https://7105.api.greenapi.com/waInstance7105215666/checkWhatsapp/96df68496897444f89ec3dc7b044d4f45b1a0365634f4ab2ba";

            // my number instance
            // $url = "https://7103.api.greenapi.com/waInstance7103137262/checkWhatsapp/671d758833a747d9b00777a1c82e4436cb5d18508aac45b29f";
            // my number instance

            $client = new \GuzzleHttp\Client();

            $response = $client->post($url, [
                'json' => [
                    'phoneNumber' => $phoneNumber,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return isset($data['existsWhatsapp']) && $data['existsWhatsapp'] === true;
        } catch (\Exception $e) {
            Log::error("Green-API checkWhatsApp failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * (Optional) Send a WhatsApp message using GreenAPI.
     */
    private function sendWhatsAppGreenApi($phoneNumber, $message)
    {
        $url = "https://7105.api.greenapi.com/waInstance7105215666/sendMessage/96df68496897444f89ec3dc7b044d4f45b1a0365634f4ab2ba";
        $chatId = ltrim($phoneNumber, '+') . '@c.us';

        $client = new \GuzzleHttp\Client();
        $client->post($url, [
            'json' => [
                'chatId'  => $chatId,
                'message' => $message,
            ],
        ]);
    }

    /**
     * Helper function to format the phone number.
     *
     * Converts numbers such as:
     * - "87076069831" => "7076069831"
     * - "+7707609831" => "7076069831"
     * - "7076069831" remains unchanged.
     */
    private function formatPhoneNumber($phone)
    {
        $phone = trim($phone);
        if (strpos($phone, '+7') === 0) {
            return '7' . substr($phone, 2);
        } elseif (strpos($phone, '8') === 0) {
            return '7' . substr($phone, 1);
        }
        return $phone;
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


}
