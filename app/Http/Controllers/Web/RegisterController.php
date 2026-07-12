<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachRegistrationRequest;
use App\Models\User;
use App\Support\ActivationDelivery;
use App\Support\MailSendSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('RegisterPage');
    }

    public function store(StoreCoachRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $coach = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);

        Auth::login($coach);
        $request->session()->regenerate();

        $emailSent = ActivationDelivery::sendCoachEmailVerification($coach);

        if (ActivationDelivery::usesManualLinks()) {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Compte créé. Invite tes athlètes et partage-leur le lien d’activation.');
        }

        return redirect()->route('verification.notice')
            ->with($emailSent ? [] : ['error' => MailSendSupport::DELIVERY_FAILED_MESSAGE]);
    }
}
