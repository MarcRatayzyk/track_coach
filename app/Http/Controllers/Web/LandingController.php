<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\AuthRedirect;
use App\Support\BillingPlans;
use App\Support\MobileApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (MobileApp::isRequest($request)) {
            return redirect()->route('login');
        }

        $user = $request->user();

        if ($user) {
            return redirect()->to(AuthRedirect::homeUrl($user));
        }

        return Inertia::render('LandingPage', [
            'pricing' => [
                'launch_discount_percent' => BillingPlans::launchDiscountPercent(),
                'eur_to_usd_rate' => BillingPlans::eurToUsdRate(),
                'plans' => BillingPlans::forFrontend(),
            ],
        ]);
    }
}
