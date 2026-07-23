<?php

namespace App\Http\Controllers\Web\Coach;

use App\Actions\AssignProgramBlockAction;
use App\Actions\BulkAssignProgramTemplateAction;
use App\Actions\BulkUpsertProgramSessionsAction;
use App\Actions\ClearProgramSessionAction;
use App\Actions\CreateProgramBlockAction;
use App\Actions\CreateStarterProgramAction;
use App\Actions\DeleteProgramBlockAction;
use App\Actions\DuplicateProgramTemplateAction;
use App\Actions\UpdateProgramBlockWarmupAction;
use App\Actions\UpsertProgramSessionAction;
use App\Http\Requests\BulkAssignProgramTemplateRequest;
use App\Http\Requests\BulkUpsertProgramSessionsRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClearProgramSessionRequest;
use App\Http\Requests\StoreProgramBlockRequest;
use App\Http\Requests\StoreProgramSessionRequest;
use App\Http\Requests\StoreStarterProgramRequest;
use App\Http\Requests\UpdateProgramBlockWarmupRequest;
use App\Models\AthleteProgramAssignment;
use App\Support\StarterProgramLibrary;
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

    public function duplicateBlock(
        AthleteProgramAssignment $assignment,
        DuplicateProgramTemplateAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);

        $assignment->loadMissing('template');

        $newAssignment = $action->execute(
            $assignment->template,
            request()->user(),
            $assignment->athlete_id,
            $assignment->date_start,
        );

        return redirect()
            ->route('program.builder', $this->builderRouteParams($newAssignment->id))
            ->with('success', 'Bloc dupliqué. Tu peux l\'ajuster puis l\'assigner.');
    }

    public function bulkAssignBlock(
        BulkAssignProgramTemplateRequest $request,
        AthleteProgramAssignment $assignment,
        BulkAssignProgramTemplateAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);

        $assignment->loadMissing('template');

        $count = $action->execute(
            $assignment->template,
            $request->user(),
            $request->input('athlete_ids', []),
            $request->date('date_start') ?? $assignment->date_start,
        );

        return redirect()
            ->route('program.builder')
            ->with('success', $count === 1
                ? 'Programme assigné à 1 athlète.'
                : "Programme assigné à {$count} athlètes.");
    }

    public function storeStarter(
        StoreStarterProgramRequest $request,
        CreateStarterProgramAction $action,
    ): RedirectResponse {
        $definition = StarterProgramLibrary::find($request->string('key')->toString());

        if ($definition === null) {
            return redirect()
                ->route('program.builder')
                ->with('error', 'Modèle de programme introuvable.');
        }

        $assignment = $action->execute(
            $definition,
            $request->user(),
            $request->integer('athlete_id'),
            $request->date('date_start'),
        );

        return redirect()
            ->route('program.builder', $this->builderRouteParams($assignment->id))
            ->with('success', 'Programme de départ créé. Personnalise-le puis assigne-le.');
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
