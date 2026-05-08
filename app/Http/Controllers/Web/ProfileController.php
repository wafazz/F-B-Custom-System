<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CustomerTier;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request, LoyaltyService $loyalty): Response
    {
        /** @var User $user */
        $user = $request->user();

        $tier = CustomerTier::with('tier')->where('user_id', $user->getKey())->first();
        $current = $tier?->tier;

        return Inertia::render('storefront/profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth ? \Illuminate\Support\Carbon::parse((string) $user->date_of_birth)->format('Y-m-d') : null,
                'gender' => $user->gender,
                'preferred_branch_id' => $user->preferred_branch_id,
                'locale' => $user->locale,
                'marketing_consent' => (bool) $user->marketing_consent,
                'whatsapp_consent' => (bool) $user->whatsapp_consent,
                'push_consent' => (bool) $user->push_consent,
                'referral_code' => $user->referral_code,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'loyalty' => [
                'balance' => $loyalty->balance($user->getKey()),
                'tier_name' => $current?->name,
                'tier_color' => $current?->color,
                'lifetime_spend' => $tier ? (float) $tier->lifetime_spend : 0.0,
            ],
            'branches' => Branch::active()->orderBy('sort_order')
                ->get(['id', 'name', 'code'])
                ->map(fn (Branch $b) => ['id' => $b->id, 'name' => $b->name, 'code' => $b->code])
                ->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->getKey())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'preferred_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'locale' => ['nullable', Rule::in(['en', 'ms'])],
            'marketing_consent' => ['boolean'],
            'whatsapp_consent' => ['boolean'],
            'push_consent' => ['boolean'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => $data['password']]);

        return back()->with('success', 'Password changed.');
    }
}
