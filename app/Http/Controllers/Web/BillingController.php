<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\BillingAccess;
use App\Support\BillingPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class BillingController extends Controller
{
    /**
     * Landing CTA: remember plan, then register or go straight to Stripe Checkout.
     */
    public function subscribe(Request $request, string $plan): RedirectResponse|SymfonyResponse
    {
        if (! array_key_exists($plan, BillingPlans::all())) {
            abort(404);
        }

        $request->session()->put('subscribe_plan', $plan);

        $user = $request->user();

        if ($user && $user->role === 'coach' && ! $user->is_demo) {
            return $this->startCheckout($request, $plan);
        }

        if ($user && $user->is_demo) {
            return redirect()
                ->route('register', ['plan' => $plan])
                ->with('error', 'Crée un vrai compte coach pour t’abonner (la démo ne peut pas payer).');
        }

        return redirect()->route('register', ['plan' => $plan]);
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'coach', 403);

        if ($user->is_demo) {
            return Inertia::render('BillingPage', [
                'plans' => array_values(BillingPlans::all()),
                'stripeConfigured' => $this->stripeConfigured(),
                'isDemo' => true,
            ]);
        }

        $autoPlan = $request->session()->pull('subscribe_plan')
            ?? $request->query('plan');

        if (is_string($autoPlan) && array_key_exists($autoPlan, BillingPlans::all()) && $this->stripeConfigured()) {
            return $this->startCheckout($request, $autoPlan);
        }

        return Inertia::render('BillingPage', [
            'plans' => array_values(BillingPlans::all()),
            'stripeConfigured' => $this->stripeConfigured(),
            'isDemo' => false,
            'billing' => BillingAccess::sharedProps($user),
        ]);
    }

    public function checkout(Request $request): SymfonyResponse|RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', Rule::in(array_keys(BillingPlans::all()))],
        ]);

        return $this->startCheckout($request, $validated['plan']);
    }

    public function checkoutPlan(Request $request, string $plan): SymfonyResponse|RedirectResponse
    {
        if (! array_key_exists($plan, BillingPlans::all())) {
            abort(404);
        }

        return $this->startCheckout($request, $plan);
    }

    private function startCheckout(Request $request, string $chosen): SymfonyResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'coach', 403);

        if ($user->is_demo) {
            return redirect()
                ->route('register')
                ->with('error', 'Les comptes démo ne peuvent pas s’abonner. Crée un vrai compte coach (essai 14 jours).');
        }

        if (! $this->stripeConfigured()) {
            return redirect()
                ->route('billing.index')
                ->with('error', 'Stripe n’est pas configuré. Renseigne STRIPE_KEY / STRIPE_SECRET et les price IDs.');
        }

        $athleteCount = $user->activeAthleteCount();
        $required = BillingPlans::requiredPlanKeyForCount(max(1, $athleteCount));

        if (! BillingPlans::meetsRequirement($chosen, $required)) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'error',
                    "Avec {$athleteCount} athlète(s), le plan minimum est « ".BillingPlans::get($required)['name'].' ».',
                );
        }

        $priceId = BillingPlans::priceIdForPlan($chosen);
        if (! $priceId) {
            return redirect()
                ->route('billing.index')
                ->with('error', 'Price ID Stripe manquant pour ce plan (STRIPE_PRICE_*).');
        }

        $request->session()->forget('subscribe_plan');

        try {
            if ($user->subscribed('default')) {
                $subscription = $user->subscription('default');
                $subscription->swap($priceId);

                return redirect()
                    ->route('billing.index')
                    ->with('success', 'Abonnement mis à jour.');
            }

            return $this->redirectToCheckout(
                $request,
                $user->newSubscription('default', $priceId)->checkout([
                    'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.index'),
                ]),
            );
        } catch (IncompletePayment $exception) {
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('billing.index')],
            );
        } catch (Throwable $exception) {
            report($exception);

            $message = 'Impossible de démarrer le paiement Stripe. Réessaie ou contacte le support.';
            if (config('app.debug')) {
                $message .= ' ('.$exception->getMessage().')';
            }

            return redirect()
                ->route('billing.index')
                ->with('error', $message);
        }
    }

    /**
     * Inertia XHR cannot follow external 303s to Stripe — use Inertia::location.
     */
    private function redirectToCheckout(Request $request, Checkout $checkout): SymfonyResponse|RedirectResponse
    {
        $url = $checkout->asStripeCheckoutSession()->url;

        if ($request->header('X-Inertia')) {
            return Inertia::location($url);
        }

        return $checkout->redirect();
    }

    public function success(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'coach', 403);

        // Clear generic trial once they subscribe so status reflects the paid plan.
        if ($user->subscribed('default') && $user->trial_ends_at) {
            $user->forceFill(['trial_ends_at' => null])->save();
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Abonnement activé. Bienvenue !');
    }

    public function portal(Request $request): SymfonyResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'coach', 403);

        if ($user->is_demo) {
            return redirect()->route('register');
        }

        if (! $this->stripeConfigured() || ! $user->stripe_id) {
            return back()->with('error', 'Aucun client Stripe associé à ce compte.');
        }

        return $user->redirectToBillingPortal(route('billing.index'));
    }

    public function blocked(Request $request): Response
    {
        abort_unless($request->user()?->role === 'athlete', 403);

        return Inertia::render('SubscriptionBlockedPage');
    }

    private function stripeConfigured(): bool
    {
        return filled(config('cashier.key')) && filled(config('cashier.secret'));
    }
}
