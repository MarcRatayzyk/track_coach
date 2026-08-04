<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachRegistrationRequest;
use App\Mail\CoachTrialStartedMail;
use App\Models\User;
use App\Support\ActivationDelivery;
use App\Support\BillingPlans;
use App\Support\MailSendSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Request $request): Response
    {
        $plan = $this->resolvePlan($request->query('plan'));

        if ($plan) {
            $request->session()->put('subscribe_plan', $plan);
        } else {
            // Inscription « essai » : ne pas reprendre un plan laissé en session via /subscribe.
            $request->session()->forget('subscribe_plan');
        }

        return Inertia::render('RegisterPage', [
            'selectedPlan' => $plan,
            'plans' => BillingPlans::forFrontend(),
        ]);
    }

    public function store(StoreCoachRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $plan = $this->resolvePlan($request->input('plan'));
        if (! $plan) {
            $request->session()->forget('subscribe_plan');
        }

        $wantsTrial = $plan === null;
        $trialDays = (int) config('billing.trial_days', 14);

        $coach = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
            // Abonnement sans paiement : pas d'essai consommé → l'utilisateur pourra démarrer l'essai plus tard.
            'trial_ends_at' => $wantsTrial ? now()->addDays($trialDays) : null,
            'email_verified_at' => $wantsTrial ? now() : null,
            'is_demo' => false,
        ]);

        Auth::login($coach);
        $request->session()->regenerate();

        if ($plan) {
            $request->session()->put('subscribe_plan', $plan);
        }

        if ($wantsTrial) {
            MailSendSupport::attempt(
                fn () => Mail::to($coach)->send(new CoachTrialStartedMail(
                    $coach,
                    $trialDays,
                    $coach->trial_ends_at?->timezone(config('app.timezone'))->format('d/m/Y') ?? '',
                    route('dashboard'),
                )),
            );

            return redirect()
                ->route('dashboard')
                ->with('success', __('messages.auth.register_created_trial_invite', ['days' => $trialDays]));
        }

        ActivationDelivery::sendCoachEmailVerification($coach);

        return redirect()
            ->route('billing.checkout.plan', ['plan' => $plan])
            ->with('success', __('messages.auth.register_created_pay'));
    }

    private function resolvePlan(mixed $plan): ?string
    {
        if (! is_string($plan) || ! array_key_exists($plan, BillingPlans::all())) {
            return null;
        }

        return $plan;
    }
}
