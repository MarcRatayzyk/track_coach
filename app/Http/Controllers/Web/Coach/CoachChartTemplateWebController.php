<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachChartTemplateRequest;
use App\Http\Requests\UpdateCoachChartTemplateRequest;
use App\Models\CoachChartTemplate;
use App\Models\CoachStatsDashboardItem;
use App\Support\ChartTemplateSupport;
use Illuminate\Http\RedirectResponse;

class CoachChartTemplateWebController extends Controller
{
    public function store(StoreCoachChartTemplateRequest $request): RedirectResponse
    {
        $payload = ChartTemplateSupport::normalizePayload($request->validated());

        $template = CoachChartTemplate::create([
            'coach_id' => $request->user()->id,
            'name' => $payload['name'],
            'config' => $payload['config'],
        ]);

        if ($request->boolean('add_to_dashboard')) {
            $this->addTemplateToDashboard($request->user()->id, $template->id);
        }

        return redirect()
            ->route('program.builder', $this->builderRouteParams($request))
            ->with('success', 'Modèle de graphique enregistré.');
    }

    public function update(UpdateCoachChartTemplateRequest $request, CoachChartTemplate $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $payload = ChartTemplateSupport::normalizePayload($request->validated());

        $template->update([
            'name' => $payload['name'],
            'config' => $payload['config'],
        ]);

        if ($request->boolean('add_to_dashboard')) {
            $this->addTemplateToDashboard($request->user()->id, $template->id);
        }

        return redirect()
            ->route('program.builder', $this->builderRouteParams($request))
            ->with('success', 'Modèle de graphique mis à jour.');
    }

    public function destroy(CoachChartTemplate $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        CoachStatsDashboardItem::query()
            ->where('coach_id', auth()->id())
            ->where('template_id', $template->id)
            ->delete();

        $template->delete();

        return redirect()
            ->route('program.builder', $this->builderRouteParams(request()))
            ->with('success', 'Modèle de graphique supprimé.');
    }

    private function authorizeTemplate(CoachChartTemplate $template): void
    {
        abort_unless($template->coach_id === auth()->id(), 403);
    }

    private function addTemplateToDashboard(int $coachId, int $templateId): void
    {
        $maxSort = CoachStatsDashboardItem::query()
            ->where('coach_id', $coachId)
            ->max('sort_order');

        CoachStatsDashboardItem::create([
            'coach_id' => $coachId,
            'item_type' => CoachStatsDashboardItem::TYPE_CUSTOM,
            'template_id' => $templateId,
            'sort_order' => ($maxSort ?? -1) + 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function builderRouteParams(\Illuminate\Http\Request $request): array
    {
        $params = ['tab' => 'stats'];

        $assignment = (int) $request->input('assignment', $request->query('assignment', 0));
        if ($assignment > 0) {
            $params['assignment'] = $assignment;
        }

        return $params;
    }
}
