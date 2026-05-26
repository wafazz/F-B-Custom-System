<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['profile' => $this->present($user)]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('users')->ignore($user->getKey())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'postcode' => ['nullable', 'string', 'max:10', 'regex:/^[0-9]{4,6}$/'],
            'state' => ['nullable', 'string', 'max:60'],
            'preferred_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'locale' => ['nullable', Rule::in(['en', 'ms'])],
            'marketing_consent' => ['sometimes', 'boolean'],
            'whatsapp_consent' => ['sometimes', 'boolean'],
            'push_consent' => ['sometimes', 'boolean'],
            'photo' => ['sometimes', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $old = null;
        if ($request->hasFile('photo')) {
            $old = $user->photo;
            $data['photo'] = $request->file('photo')->store('avatars', 'public');
        }

        $user->update($data);

        // Prune the replaced upload only if it lived on the public disk —
        // external avatar URLs (e.g. social login) won't resolve there.
        if ($old !== null && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        return response()->json(['profile' => $this->present($user->fresh() ?? $user)]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $path = $request->file('photo')->store('avatars', 'public');

        $old = $user->photo;
        $user->update(['photo' => $path]);

        // Only prune the previous file if it was a stored upload — external
        // avatar URLs (e.g. social login) won't resolve on the public disk.
        if ($old !== null && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        return response()->json(['profile' => $this->present($user->fresh() ?? $user)]);
    }

    public function dataExport(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->getKey())
            ->latest()
            ->with(['items.modifiers', 'branch:id,name'])
            ->limit(500)
            ->get();

        return response()->json([
            'profile' => $this->present($user),
            'orders' => $orders
                ->map(fn (Order $o) => [
                    'number' => $o->number,
                    'status' => $o->status->value,
                    'total' => (float) $o->total,
                    'created_at' => $o->created_at?->toIso8601String(),
                    'branch' => $o->branch?->name,
                    'items' => $o->items->map(fn ($i) => [
                        'product' => $i->product_name,
                        'quantity' => $i->quantity,
                        'unit_price' => (float) $i->unit_price,
                        'modifiers' => $i->modifiers->pluck('option_name'),
                    ]),
                ])
                ->values(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user) {
            // Match the web AccountController behaviour — anonymise + soft delete.
            $user->forceFill([
                'name' => 'Deleted user',
                'email' => 'deleted-'.$user->getKey().'@example.invalid',
                'phone' => null,
                'address_line' => null,
                'city' => null,
                'postcode' => null,
                'state' => null,
                'date_of_birth' => null,
                'photo' => null,
            ])->save();
            $user->tokens()->delete();
            PushSubscription::query()->where('user_id', $user->getKey())->delete();
            Order::query()->where('user_id', $user->getKey())->update(['customer_snapshot' => null]);
            $user->delete();
        });

        return response()->json(['ok' => true]);
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
            'address_line' => $user->address_line,
            'city' => $user->city,
            'postcode' => $user->postcode,
            'state' => $user->state,
            'photo' => $user->photo,
            'preferred_branch_id' => $user->preferred_branch_id,
            'locale' => $user->locale,
            'marketing_consent' => (bool) $user->marketing_consent,
            'whatsapp_consent' => (bool) $user->whatsapp_consent,
            'push_consent' => (bool) $user->push_consent,
            'referral_code' => $user->referral_code,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
