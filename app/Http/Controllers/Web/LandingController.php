<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            if ($user->role === 'coach') {
                return redirect()->route('dashboard');
            }

            return redirect()->route('athlete.dashboard');
        }

        return Inertia::render('LandingPage');
    }
}
