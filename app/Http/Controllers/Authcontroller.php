<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PhoneVerification;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login and return user details with token.
     */
    public function login(Request $request)
    {
        /* 1. Валидация ввода ------------------------------------ */
        $request->validate([
            'whatsapp_number' => 'required',
            'password'        => 'required',
        ]);

        $phone = $this->formatPhoneNumber($request->whatsapp_number);

        if (!Auth::attempt(['whatsapp_number' => $phone,
                            'password'        => $request->password])) {
            return response()->json(
                ['message' => 'Неправильный логин или пароль'],
                401
            );
        }

        /** @var \App\Models\User $user */
        $user = Auth::user()->load([
            'roles:id,name',
            'permissions:id,code,name',
            'organization:id,name',
            'roles.permissions:id,code,name',
            'organization.plans.permissions:id,code,name',
        ]);

        /* 2. Итоговый набор прав (без pivot) -------------------- */
        $permissions = $user->allPermissions()
                            ->map(function ($p) {
                                return [
                                    'id'   => $p->id,
                                    'code' => $p->code,
                                    'name' => $p->name,
                                ];
                            })
                            ->values();        // Collection → array JSON-friendly

        $profile = [
            'id'              => $user->id,
            'roles'           => $user->roles->pluck('name'),
            'organization'    => $user->organization,
            'first_name'      => $user->first_name,
            'last_name'       => $user->last_name,
            'surname'         => $user->surname,
            'whatsapp_number' => $user->whatsapp_number,
            'photo'           => $user->photo ? asset('storage/'.$user->photo) : null,
            'permissions'     => $permissions,
        ];

        /* 3. Телефон не подтверждён → 423 ----------------------- */
        if (is_null($user->phone_verified_at)) {
            return response()->json(
                $profile + [
                    'is_verified' => false,
                    'message'     => 'Телефон не подтверждён. Введите код из WhatsApp/SMS.',
                ],
                423
            );
        }

        /* 4. Всё ок → отдаём токен и успех ---------------------- */
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(
            $profile + [
                'is_verified' => true,
                'token'       => $token,
            ],
            200
        );
    }


    /**
     * Register a new user and send a Twilio verification code.
     */
    public function register(Request $request)
    {
        Log::info('Register Request', $request->all());

        try {
            // Validate input
            $fields = $request->validate(
                [
                    'first_name'      => 'required|string|max:255',
                    'last_name'       => 'nullable|string|max:255',
                    'surname'         => 'nullable|string|max:255',
                    'whatsapp_number' => 'required|string|unique:users|max:15',
                    'password'        => 'required|string|confirmed|min:8',
                ],
                [
                    'whatsapp_number.unique' => 'whatsapp номер уже зарегистрирован',   // 👈 ваше сообщение
                    'password.confirmed'    => 'пароли не совпадают',
                    // … добавляйте остальные при желании
                ]
            );


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
            $this->deliverVerificationCode($formattedNumber);

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


    public function registerOrganization(Request $request): JsonResponse
{
    /* ① валидация */
    $data = $request->validate([
        'org_name'      => 'required|string|max:255',
        'address'       => 'nullable|string|max:255',
        'plan_slug'     => 'nullable|string|exists:plans,slug',   // null → starter
        'manager'       => 'required|array',
        'manager.phone' => 'required|string',
        'manager.first_name' => 'required|string|max:255',
        'manager.last_name'  => 'nullable|string|max:255',
        'manager.password'   => 'required|string|min:8',
    ]);

    /* ② атомарная транзакция */
    DB::transaction(function () use ($data) {

        /* ── 2.1 Организация ── */
        $org = Organization::create([
            'id'      => (string) Str::uuid(),
            'name'    => $data['org_name'],
            'address' => $data['address'] ?? '',
            'manager_first_name' => $data['manager']['first_name'],
            'manager_last_name'  => $data['manager']['last_name'] ?? '',
            'manager_phone'      => $data['manager']['phone'],
            'manager_role'       => 'admin',
        ]);

        /* ── 2.2 План ── */
        $plan = Plan::where('slug', $data['plan_slug'] ?? 'starter')->first();

        // привязываем к организации
        $org->plans()->attach($plan->id, [
            'starts_at' => now(),
            'ends_at'   => now()->addDays($plan->period_days),
        ]);

        /* ── 2.3 Админ ── */
        $phone10 = $this->formatPhoneNumber($data['manager']['phone']);

        $admin = User::create([
            'first_name'      => $data['manager']['first_name'],
            'last_name'       => $data['manager']['last_name'] ?? '',
            'whatsapp_number' => $phone10,
            'password'        => Hash::make($data['manager']['password']),
            'organization_id' => $org->id,
        ]);
        $admin->assignRole('admin');

        /* ── 2.4 Отправляем код ── */
        $this->deliverVerificationCode($phone10);
    });

    return response()->json([
        'message' => 'Организация зарегистрирована, код выслан менеджеру.'
    ], 201);
}


    /**
     * Generate a random 4-digit code, store it in PhoneVerification table,
     * and send it via GreenAPI.
     */
    public function sendVerificationCode(Request $request)
{
Log::info("hererrerrerrerr");
Log::info($request);
    $request->validate(['phone_number' => 'required|string']);
    $phone = $this->formatPhoneNumber($request->phone_number);

    $this->deliverVerificationCode($phone);   // 👈 приватный метод
    return response()->json(['message' => 'Код отправлен']);
}

/*  внутренняя отправка: используем и из register()  */
private function deliverVerificationCode(string $phone10): void
{
    Log::info("helellelele");
    Log::info($phone10);
    $code = random_int(1000, 9999);

    PhoneVerification::create([
        'phone_number'    => $phone10,
        'code'            => $code,
        'organization_id' => null,   // или $orgId, если нужен
    ]);

    /* отправляем через GreenAPI */
    $chatId = '7'.$phone10.'@c.us';

    Http::post(
        'https://7105.api.greenapi.com/waInstance7105237391/sendMessage/70f842bef4ac4b49a48061f033e03752846596508a9847638a',
        ['chatId' => $chatId, 'message' => "Ваш код: $code"]
    );
}



    /**
     * Verify the code entered by the user.
     */
    public function verifyCode(Request $request)
{
    Log::info("verficacionnny code");
    Log::info($request->all());

    $request->validate([
        'phone_number' => 'required|string',
        'code'         => 'required|string',
    ]);

    $phone10 = $this->formatPhoneNumber($request->phone_number);

    /* ① ищем запись */
    $verification = PhoneVerification::where('phone_number', $phone10)
                                     ->where('code', $request->code)
                                     ->first();

    /* ② не нашли — сразу ошибка */
    if (! $verification) {
        return response()->json([
            'message' => 'Неверный код проверки'
        ], 422);                                          // 422 Unprocessable Entity
    }

    /* ③ опционально проверяем срок действия (5 мин) */
    // if ($verification->created_at->lt(now()->subMinutes(5))) {
    //     $verification->delete();                          // удалить просроченный
    //     return response()->json([
    //         'message' => 'Срок действия кода истёк'
    //     ], 422);
    // }

    /* ④ есть корректная запись — подтверждаем */
    $verification->delete();

    User::where('whatsapp_number', $phone10)
        ->update(['phone_verified_at' => now()]);

    return response()->json(['message' => 'Код подтверждён']);
}

    /**
     * Check if the given phone number is registered with WhatsApp using GreenAPI.
     */
    private function checkWhatsappGreenApi($phone10)
{
    try {
        $url = 'https://7105.api.greenapi.com/waInstance7105237391/checkWhatsapp/70f842bef4ac4b49a48061f033e03752846596508a9847638a';

        $client   = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'json' => [ 'phoneNumber' => '7'.$phone10 ],  // 👈 GreenAPI ждёт 11 цифр
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['existsWhatsapp'] ?? false;
    } catch (\Exception $e) {
        Log::error('Green-API check failed: '.$e->getMessage());
        return false;
    }
}


    /**
     * (Optional) Send a WhatsApp message using GreenAPI.
     */
    private function sendWhatsAppGreenApi($phoneNumber, $message)
    {
        $url = 'https://7105.api.greenapi.com/waInstance7105237391/sendMessage/70f842bef4ac4b49a48061f033e03752846596508a9847638a';
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
    /**
 * Приводит казахстанский номер к формату 10-ти цифр: 7076069831
 * Допускает входные варианты: +7707…, 8707…, 7707…, 7077…
 */
/**
 * Приводит номер к формату GreenAPI: 11-цифр, начинается с 7.
 * Принимает варианты: "+7705…", "8705…", "7705…", "705…" (с пробелами/скобками).
 */
private function formatPhoneNumber(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone);   // только цифры

    // +770… / 770… / 870… / 70… → 707…
    if (strlen($d) === 12 && substr($d, 0, 2) === '77') return substr($d, 2);
    if (strlen($d) === 11 && $d[0] === '7')             return substr($d, 1);
    if (strlen($d) === 11 && $d[0] === '8')             return substr($d, 1);
    // если уже 10 цифр, оставляем
    return $d;
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
