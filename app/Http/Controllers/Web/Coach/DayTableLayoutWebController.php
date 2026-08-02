<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDayTableLayoutRequest;
use App\Http\Requests\UpdateDayTableLayoutRequest;
use App\Models\DayTableLayout;
use App\Support\DayTableLayoutSupport;
use Illuminate\Http\RedirectResponse;

class DayTableLayoutWebController extends Controller
{
    public function store(StoreDayTableLayoutRequest $request): RedirectResponse
    {
        $payload = DayTableLayoutSupport::normalizePayload($request->validated());

        if ($payload['is_default']) {
            DayTableLayout::query()
                ->where('coach_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        DayTableLayout::create([
            'coach_id' => $request->user()->id,
            ...$payload,
        ]);

        return redirect()
            ->route('program.builder', ['tab' => 'table'])
            ->with('success', __('messages.day_table.saved'));
    }

    public function update(UpdateDayTableLayoutRequest $request, DayTableLayout $layout): RedirectResponse
    {
        $this->authorizeLayout($layout);

        $payload = DayTableLayoutSupport::normalizePayload($request->validated());

        if ($payload['is_default']) {
            DayTableLayout::query()
                ->where('coach_id', $request->user()->id)
                ->whereKeyNot($layout->id)
                ->update(['is_default' => false]);
        }

        $layout->update($payload);

        return redirect()
            ->route('program.builder', ['tab' => 'table'])
            ->with('success', __('messages.day_table.updated'));
    }

    public function destroy(DayTableLayout $layout): RedirectResponse
    {
        $this->authorizeLayout($layout);

        $remainingCount = DayTableLayout::query()
            ->where('coach_id', auth()->id())
            ->whereKeyNot($layout->id)
            ->count();

        if ($remainingCount === 0) {
            return redirect()
                ->route('program.builder', ['tab' => 'table'])
                ->with('error', __('messages.day_table.keep_at_least_one'));
        }

        $wasDefault = $layout->is_default;
        $coachId = $layout->coach_id;
        $layout->delete();

        if ($wasDefault) {
            DayTableLayout::query()
                ->where('coach_id', $coachId)
                ->orderBy('id')
                ->first()
                ?->update(['is_default' => true]);
        }

        return redirect()
            ->route('program.builder', ['tab' => 'table'])
            ->with('success', __('messages.day_table.deleted'));
    }

    private function authorizeLayout(DayTableLayout $layout): void
    {
        abort_unless($layout->coach_id === auth()->id(), 403);
    }
}
