<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachRegistrationRequest;
use App\Models\User;
use App\Support\ActivationDelivery;
use App\Support\BillingPlans;
use App\Support\MailSendSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Request $request): Response
    {
        $plan = $request->query('plan', $request->session()->get('subscribe_plan'));
        if (! is_string($plan) || ! array_key_exists($plan, BillingPlans::all())) {
            $plan = null;
        }

        if ($plan) {
            $request->session()->put('subscribe_plan', $plan);
        }

        return Inertia::render('RegisterPage', [
            'selectedPlan' => $plan,
            'plans' => array_values(BillingPlans::all()),
        ]);
    }

    public function store(StoreCoachRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $plan = $request->input('plan', $request->session()->get('subscribe_plan'));
        if (! is_string($plan) || ! array_key_exists($plan, BillingPlans::all())) {
            $plan = null;
        }

        $coach = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
            'trial_ends_at' => now()->addDays((int) config('billing.trial_days', 14)),
            'is_demo' => false,
        ]);

        Auth::login($coach);
        $request->session()->regenerate();

        if ($plan) {
            $request->session()->put('subscribe_plan', $plan);
        }

        $emailSent = ActivationDelivery::sendCoachEmailVerification($coach);

        if ($plan) {
            return redirect()
                ->route('billing.checkout.plan', ['plan' => $plan])
                ->with('success', 'Compte créé — finalise ton paiement pour activer l’abonnement.');
        }

        if (ActivationDelivery::usesManualLinks()) {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Compte créé. Invite tes athlètes et partage-leur le lien d’activation.');
        }

        return redirect()->route('verification.notice')
            ->with($emailSent ? [] : ['error' => MailSendSupport::DELIVERY_FAILED_MESSAGE]);
    }
}
