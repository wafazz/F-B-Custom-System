<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Vouchers\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    public function store(Request $request, VoucherService $vouchers): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
        ]);

        $referrer = ! empty($data['referral_code'])
            ? User::where('referral_code', $data['referral_code'])->first()
            : null;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'referred_by' => $referrer?->id,
        ]);

        $user->assignRole('customer');

        // Welcome vouchers — any active voucher with new_users_only=true
        // that the new user qualifies for is auto-claimed on their behalf.
        $vouchers->autoIssueWelcomeVouchers($user);

        Auth::login($user);

        return redirect('/');
    }
}
