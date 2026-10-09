<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\ActivationDelivery;
use App\Support\AuthRedirect;
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
        if (ActivationDelivery::usesManualLinks()) {
            ActivationDelivery::markCoachEmailVerified($request->user());

            return AuthRedirect::afterLogin($request, $request->user());
        }

        if ($request->user()->hasVerifiedEmail()) {
            return AuthRedirect::afterLogin($request, $request->user());
        }

        return Inertia::render('VerifyEmailPage', [
            'status' => $request->session()->get('status'),
            'mailError' => $request->session()->get('error'),
            'trialDays' => (int) config('billing.trial_days', 14),
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        $user = $request->user();

        if ($user->role === 'coach' && $user->initial_setup_completed_at === null) {
            $user->forceFill(['initial_setup_completed_at' => now()])->save();
        }

        return AuthRedirect::afterLogin($request, $user)
            ->with('success', __('messages.auth.email_confirmed'));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return AuthRedirect::afterLogin($request, $request->user());
        }

        if (ActivationDelivery::usesManualLinks()) {
            ActivationDelivery::markCoachEmailVerified($request->user());

            return AuthRedirect::afterLogin($request, $request->user());
        }

        $sent = ActivationDelivery::sendCoachEmailVerification($request->user());

        if (! $sent) {
            return back()->with('error', MailSendSupport::deliveryFailedMessage());
        }

        return back()->with('status', 'verification-link-sent');
    }
}
