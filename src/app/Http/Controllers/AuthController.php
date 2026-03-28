<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\SmsAeroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function __construct(
        private PhoneVerificationService $verification,
        private SmsAeroService $sms,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'name'     => $request->name,
        ]);

        $code = $this->verification->generateCode($request->phone);
        $this->sms->send($request->phone, "Ваш код подтверждения: {$code}");

        return response()->json([
            'message' => 'Код отправлен на номер ' . $request->phone,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Неверный email или пароль.',
            ]);
        }

        $request->session()->regenerate();

        return response()->json(['user' => Auth::user()]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function verifyPhone(VerifyPhoneRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)->firstOrFail();

        if (!$this->verification->verify($request->phone, $request->code)) {
            return response()->json([
                'message' => 'Неверный или истёкший код.',
            ], 422);
        }

        $user->update(['phone_verified_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user]);
    }
}
