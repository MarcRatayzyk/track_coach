<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\MoveCoachStatsDashboardItemRequest;
use App\Http\Requests\StoreCoachStatsDashboardItemRequest;
use App\Models\CoachChartTemplate;
use App\Models\CoachStatsDashboardItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CoachStatsDashboardWebController extends Controller
{
    public function store(StoreCoachStatsDashboardItemRequest $request): RedirectResponse
    {
        $template = CoachChartTemplate::query()->findOrFail($request->integer('template_id'));
        $this->authorizeTemplate($template);

        $maxSort = CoachStatsDashboardItem::query()
            ->where('coach_id', $request->user()->id)
            ->max('sort_order');

        CoachStatsDashboardItem::create([
            'coach_id' => $request->user()->id,
            'item_type' => CoachStatsDashboardItem::TYPE_CUSTOM,
            'template_id' => $template->id,
            'sort_order' => ($maxSort ?? -1) + 1,
        ]);

        return redirect()
            ->route('program.builder', $this->builderRouteParams($request))
            ->with('success', 'Graphique ajouté au tableau de bord.');
    }

    public function destroy(CoachStatsDashboardItem $item): RedirectResponse
    {
        $this->authorizeItem($item);
        $item->delete();

        return redirect()
            ->route('program.builder', $this->builderRouteParams(request()))
            ->with('success', 'Graphique retiré du tableau de bord.');
    }

    public function move(MoveCoachStatsDashboardItemRequest $request, CoachStatsDashboardItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $items = CoachStatsDashboardItem::query()
            ->where('coach_id', auth()->id())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $items->search(fn (CoachStatsDashboardItem $candidate) => $candidate->id === $item->id);

        if ($index === false) {
            return redirect()
                ->route('program.builder', $this->builderRouteParams($request));
        }

        $swapIndex = $request->input('direction') === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= $items->count()) {
            return redirect()
                ->route('program.builder', $this->builderRouteParams($request));
        }

        $other = $items[$swapIndex];
        $currentOrder = $item->sort_order;
        $item->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $currentOrder]);

        return redirect()
            ->route('program.builder', $this->builderRouteParams($request));
    }

    private function authorizeItem(CoachStatsDashboardItem $item): void
    {
        abort_unless($item->coach_id === auth()->id(), 403);
    }

    private function authorizeTemplate(CoachChartTemplate $template): void
    {
        abort_unless($template->coach_id === auth()->id(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function builderRouteParams(Request $request): array
    {
        $params = ['tab' => 'stats'];

        $assignment = (int) $request->input('assignment', $request->query('assignment', 0));
        if ($assignment > 0) {
            $params['assignment'] = $assignment;
        }

        return $params;
    }
}
