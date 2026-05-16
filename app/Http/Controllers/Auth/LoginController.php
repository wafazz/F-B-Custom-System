<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/branches');
        }

        if ($redirect = $request->query('redirect')) {
            $request->session()->put('url.intended', (string) $redirect);
        }

        return Inertia::render('auth/login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Stay logged in for 30 days (60 × 24 × 30 minutes). Remember cookie
        // revives the session after the short session-cookie expires, so the
        // customer stays in until they manually log out or 30 days elapse.
        $guard = Auth::guard('web');
        if ($guard instanceof SessionGuard) {
            $guard->setRememberDuration(60 * 24 * 30);
        }

        if (! Auth::attempt(
            [$field => $credentials['identifier'], 'password' => $credentials['password']],
            true,
        )) {
            throw ValidationException::withMessages([
                'identifier' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/branches');
    }
}
