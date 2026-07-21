<?php

namespace App\Http\Controllers\Web\Coach;

use App\Actions\AssignProgramBlockAction;
use App\Actions\BulkUpsertProgramSessionsAction;
use App\Actions\ClearProgramSessionAction;
use App\Actions\CreateProgramBlockAction;
use App\Actions\DeleteProgramBlockAction;
use App\Actions\UpdateProgramBlockWarmupAction;
use App\Actions\UpsertProgramSessionAction;
use App\Http\Requests\BulkUpsertProgramSessionsRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClearProgramSessionRequest;
use App\Http\Requests\StoreProgramBlockRequest;
use App\Http\Requests\StoreProgramSessionRequest;
use App\Http\Requests\UpdateProgramBlockWarmupRequest;
use App\Models\AthleteProgramAssignment;
use Illuminate\Http\RedirectResponse;

class ProgramWebController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function builderRouteParams(int $assignmentId, ?string $tab = null): array
    {
        $params = ['assignment' => $assignmentId];

        if (is_string($tab) && in_array($tab, ['calendar', 'table', 'table_v2', 'stats'], true)) {
            $params['tab'] = $tab;
        }

        return $params;
    }

    public function storeBlock(
        StoreProgramBlockRequest $request,
        CreateProgramBlockAction $action,
    ): RedirectResponse {
        $assignment = $action->execute($request);
        $tab = $request->input('builder_tab');

        return redirect()
            ->route('program.builder', $this->builderRouteParams($assignment->id, is_string($tab) ? $tab : null))
            ->with('success', 'Bloc créé. Commencez à construire vos séances.');
    }

    public function destroyBlock(
        AthleteProgramAssignment $assignment,
        DeleteProgramBlockAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);

        $action->execute($assignment);

        return redirect()
            ->route('program.builder')
            ->with('success', 'Bloc supprimé.');
    }

    public function assignBlock(
        AthleteProgramAssignment $assignment,
        AssignProgramBlockAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);
        $tab = request()->input('builder_tab', request()->query('tab'));

        $action->execute($assignment);

        return redirect()
            ->route('program.builder', $this->builderRouteParams($assignment->id, is_string($tab) ? $tab : null))
            ->with('success', 'Bloc enregistré et assigné à l\'athlète.');
    }

    public function updateWarmup(
        UpdateProgramBlockWarmupRequest $request,
        AthleteProgramAssignment $assignment,
        UpdateProgramBlockWarmupAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);
        $tab = $request->input('builder_tab', $request->query('tab'));

        $action->execute($request, $assignment);

        return redirect()
            ->route('program.builder', $this->builderRouteParams($assignment->id, is_string($tab) ? $tab : null))
            ->with('success', 'Échauffement du bloc enregistré.');
    }

    public function upsertSession(
        StoreProgramSessionRequest $request,
        AthleteProgramAssignment $assignment,
        UpsertProgramSessionAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);
        $tab = $request->input('builder_tab', $request->query('tab'));

        $action->execute($request, $assignment);

        return redirect()
            ->route('program.builder', $this->builderRouteParams($assignment->id, is_string($tab) ? $tab : null))
            ->with('success', 'Séance enregistrée.');
    }

    public function bulkUpsertSessions(
        BulkUpsertProgramSessionsRequest $request,
        AthleteProgramAssignment $assignment,
        BulkUpsertProgramSessionsAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);
        $tab = $request->input('builder_tab', $request->query('tab'));

        $count = $action->execute($request, $assignment);

        return redirect()
            ->route('program.builder', $this->builderRouteParams($assignment->id, is_string($tab) ? $tab : null))
            ->with('success', $count === 1 ? '1 séance collée.' : "{$count} séances collées.");
    }

    public function clearSession(
        ClearProgramSessionRequest $request,
        AthleteProgramAssignment $assignment,
        ClearProgramSessionAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);
        $tab = $request->input('builder_tab', $request->query('tab'));

        $action->execute($request, $assignment);

        return redirect()
            ->route('program.builder', $this->builderRouteParams($assignment->id, is_string($tab) ? $tab : null))
            ->with('success', 'Case vidée.');
    }
}
