<?php

namespace App\Http\Controllers\Web;

use App\Actions\ClaimSelfCoachedProgramAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AthleteSelfCoachingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $athlete = $request->user();

        abort_unless(
            $athlete !== null && $athlete->role === 'athlete' && ! $athlete->isSelfCoached(),
            403,
        );

        DB::transaction(function () use ($athlete): void {
            $athlete->forceFill([
                'self_coached' => true,
                'initial_setup_completed_at' => $athlete->initial_setup_completed_at ?? now(),
            ])->save();

            $athlete->coaches()->detach();

            app(ClaimSelfCoachedProgramAction::class)->claimAll($athlete);
        });

        return redirect()
            ->route('athlete.dashboard')
            ->with('success', __('messages.athletes.now_self_coached'));
    }
}
