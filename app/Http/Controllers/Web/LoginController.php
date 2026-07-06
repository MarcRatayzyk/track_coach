<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MobileApp;
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
            return $this->redirectAuthenticatedUser($request->user());
        }

        return Inertia::render('LoginPage', [
            'email' => old('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember') || MobileApp::isRequest($request);

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        $user = Auth::user();

        if ($user->initial_setup_completed_at === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->role === 'coach'
                ? 'Active ton compte avec le lien d’invitation reçu par e-mail avant de te connecter.'
                : 'Active ton compte avec le lien d’invitation envoyé par ton coach avant de te connecter.';

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        $request->session()->regenerate();

        if ($user->role === 'coach' && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->role === 'coach') {
            return redirect()->intended(route('dashboard'));
        }

        return redirect()->intended(route('athlete.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route(
            MobileApp::isRequest($request) ? 'login' : 'home',
        );
    }

    private function redirectAuthenticatedUser(User $user): RedirectResponse
    {
        if ($user->role === 'coach' && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->role === 'coach') {
            return redirect()->route('dashboard');
        }

        return redirect()->route('athlete.dashboard');
    }
}
