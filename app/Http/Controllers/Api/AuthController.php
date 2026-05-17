<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::default()],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ]);

        /** @var User|null $referrer */
        $referrer = null;
        if (! empty($data['referral_code'])) {
            $referrer = User::query()
                ->where('referral_code', strtoupper((string) $data['referral_code']))
                ->first();
        }

        /** @var User $user */
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make((string) $data['password']),
            'referral_code' => $this->generateReferralCode(),
            'referred_by' => $referrer?->getKey(),
        ]);
        $user->assignRole('customer');

        return response()->json([
            'user' => $this->present($user),
            'token' => $user->createToken('mobile', ['*'])->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $field = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        /** @var User|null $user */
        $user = User::query()->where($field, $data['identifier'])->first();
        if (! $user || ! Hash::check((string) $data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['These credentials do not match our records.'],
            ]);
        }

        $deviceName = (string) ($data['device_name'] ?? 'mobile');

        return response()->json([
            'user' => $this->present($user),
            'token' => $user->createToken($deviceName, ['*'])->plainTextToken,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['user' => $this->present($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json(['ok' => true]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::default()],
        ]);

        /** @var User $user */
        $user = $request->user();
        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill(['password' => Hash::make((string) $data['password'])])->save();
        $user->tokens()->where('id', '!=', $request->user()?->currentAccessToken()?->id)->delete();

        return response()->json(['ok' => true]);
    }

    protected function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }

    /** @return array<string, mixed> */
    protected function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'date_of_birth' => $user->date_of_birth
                ? \Illuminate\Support\Carbon::parse($user->date_of_birth)->format('Y-m-d')
                : null,
            'gender' => $user->gender,
            'photo' => $user->photo,
            'referral_code' => $user->referral_code,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
