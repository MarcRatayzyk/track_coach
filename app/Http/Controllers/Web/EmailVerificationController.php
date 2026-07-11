<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\MailSendSupport;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): Response|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->homeFor($request->user()));
        }

        return Inertia::render('VerifyEmailPage', [
            'status' => $request->session()->get('status'),
            'mailError' => $request->session()->get('error'),
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        $user = $request->user();

        if ($user->role === 'coach' && $user->initial_setup_completed_at === null) {
            $user->forceFill(['initial_setup_completed_at' => now()])->save();
        }

        return redirect()->intended($this->homeFor($user))
            ->with('success', 'E-mail confirmé. Bienvenue sur Track Coach !');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->homeFor($request->user()));
        }

        $sent = MailSendSupport::attempt(
            fn () => $request->user()->sendEmailVerificationNotification(),
        );

        if (! $sent) {
            return back()->with('error', MailSendSupport::DELIVERY_FAILED_MESSAGE);
        }

        return back()->with('status', 'verification-link-sent');
    }

    private function homeFor(\App\Models\User $user): string
    {
        return $user->role === 'coach'
            ? route('dashboard')
            : route('athlete.dashboard');
    }
}
