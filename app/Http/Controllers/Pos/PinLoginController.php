<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PinLoginController extends Controller
{
    public function show(): Response
    {
        $branches = Branch::active()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name'])
            ->map(fn (Branch $b) => ['id' => $b->id, 'code' => $b->code, 'name' => $b->name])
            ->values();

        return Inertia::render('pos/login', [
            'branches' => $branches,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'pin' => ['required', 'string', 'min:4', 'max:6'],
        ]);

        $rows = BranchStaff::query()
            ->where('branch_id', $data['branch_id'])
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->get(['user_id', 'pin']);

        $matched = $rows->first(fn (BranchStaff $row) => Hash::check($data['pin'], (string) $row->getAttribute('pin')));
        if (! $matched) {
            throw ValidationException::withMessages(['pin' => 'Invalid PIN for this branch.']);
        }

        $user = User::find($matched->getAttribute('user_id'));
        if (! $user) {
            throw ValidationException::withMessages(['pin' => 'Staff record not found.']);
        }

        $request->session()->put('pos.user_id', $user->getKey());
        $request->session()->put('pos.user_name', $user->name);
        $request->session()->put('pos.branch_id', (int) $data['branch_id']);

        return redirect()->route('pos.queue');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['pos.user_id', 'pos.user_name', 'pos.branch_id']);

        return redirect()->route('pos.login');
    }
}
