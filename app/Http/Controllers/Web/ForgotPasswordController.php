<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\MailSendSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('ForgotPasswordPage', [
            'email' => old('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $sent = MailSendSupport::attempt(
            fn () => Password::sendResetLink($request->only('email')),
        );

        if (! $sent) {
            return back()->with('error', MailSendSupport::deliveryFailedMessage());
        }

        return back()->with('success', __('messages.auth.forgot_password_sent'));
    }
}
