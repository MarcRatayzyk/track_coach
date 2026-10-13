<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AthleteSelfCoachingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $athlete = $request->user();

        abort_unless(
            $athlete !== null && $athlete->role === 'athlete' && ! $athlete->isSelfCoached(),
            403,
        );

        $athlete->forceFill([
            'self_coached' => true,
            'initial_setup_completed_at' => $athlete->initial_setup_completed_at ?? now(),
        ])->save();

        $athlete->coaches()->detach();

        return redirect()
            ->route('athlete.dashboard')
            ->with('success', __('messages.athletes.now_self_coached'));
    }
}
