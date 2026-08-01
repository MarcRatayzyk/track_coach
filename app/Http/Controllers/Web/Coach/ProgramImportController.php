<?php

namespace App\Http\Controllers\Web\Coach;

use App\Actions\BulkUpsertProgramSessionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyProgramImportRequest;
use App\Http\Requests\PreviewProgramImportRequest;
use App\Http\Requests\PreviewProgramJsonImportRequest;
use App\Models\AthleteProgramAssignment;
use App\Support\ProgramImport\EnsureProgramWeeksForImport;
use App\Support\ProgramImport\ExerciseNameMatcher;
use App\Support\ProgramImport\ImportedExerciseResolver;
use App\Support\ProgramImport\ProgramAiImporter;
use App\Support\ProgramImport\ProgramCsvColumns;
use App\Support\ProgramImport\ProgramImportDraftBuilder;
use App\Support\ProgramImport\ProgramJsonImportNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramImportController extends Controller
{
    public function template(): StreamedResponse
    {
        $csv = ProgramCsvColumns::templateCsv();

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            'track-coach-programme-template.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ],
        );
    }

    public function meta(ProgramAiImporter $ai, Request $request): JsonResponse
    {
        $weekCount = max(0, (int) $request->query('week_count', 0));
        $daysPerWeek = max(1, min(7, (int) $request->query('days_per_week', 4)));
        $normalizer = new ProgramJsonImportNormalizer;
        $skeletonWeeks = $weekCount > 0 ? $weekCount : 5;

        return response()->json([
            'ai_enabled' => $ai->isConfigured(),
            'vision_enabled' => $ai->isConfigured(),
            'official_columns' => ProgramCsvColumns::official(),
            'json_format' => ProgramJsonImportNormalizer::FORMAT,
            'json_template' => $normalizer->skeleton($skeletonWeeks, $daysPerWeek),
            'external_ai_prompt' => $normalizer->externalAiPrompt($weekCount > 0 ? $weekCount : $skeletonWeeks),
        ]);
    }

    public function jsonTemplate(Request $request): JsonResponse
    {
        $weekCount = max(1, min(16, (int) $request->query('week_count', 5)));
        $daysPerWeek = max(1, min(7, (int) $request->query('days_per_week', 4)));
        $normalizer = new ProgramJsonImportNormalizer;

        return response()->json([
            'format' => ProgramJsonImportNormalizer::FORMAT,
            'template' => $normalizer->skeleton($weekCount, $daysPerWeek),
            'external_ai_prompt' => $normalizer->externalAiPrompt($weekCount),
        ]);
    }

    public function previewJson(
        PreviewProgramJsonImportRequest $request,
        AthleteProgramAssignment $assignment,
    ): JsonResponse {
        $this->authorize('manage', $assignment);

        try {
            $rows = (new ProgramJsonImportNormalizer)->normalize($request->input('json'));
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['json' => [$e->getMessage()]],
            ], 422);
        }

        $draft = $this->buildDraft($request->user(), $assignment, $rows);

        return response()->json([
            'status' => 'ready',
            'source' => 'json',
            ...$draft,
        ]);
    }

    public function preview(
        PreviewProgramImportRequest $request,
        AthleteProgramAssignment $assignment,
        ProgramAiImporter $ai,
    ): JsonResponse {
        $this->authorize('manage', $assignment);

        $seconds = max(180, (int) config('program_import.timeout_seconds', 180));
        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds + 60);
        }
        @ini_set('max_execution_time', (string) ($seconds + 60));

        try {
            $assignment->loadMissing('template.weeks');
            $weekCount = (int) ($assignment->template?->weeks?->count() ?? 0);
            $rows = $ai->extractRows($request->file('file'), (int) $request->user()->id, $weekCount);
        } catch (\App\Support\ProgramImport\ProgramAiResponseTruncated $e) {
            return response()->json([
                'message' => 'Réponse IA encore trop longue après découpage. Réessaie avec un PDF, ou utilise l’onglet JSON (IA externe).',
            ], 422);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'Maximum execution time') || str_contains($message, 'max_execution_time')) {
                return response()->json([
                    'message' => 'Analyse trop longue (timeout PHP). Réessaie, ou importe un CSV/XLSX plutôt qu’une capture d’écran très lourde.',
                ], 504);
            }

            throw $e;
        }

        $draft = $this->buildDraft($request->user(), $assignment, $rows);

        return response()->json([
            'status' => 'ready',
            'source' => 'ai',
            ...$draft,
        ]);
    }

    /** @deprecated Use preview() — kept for older clients */
    public function previewCsv(
        PreviewProgramImportRequest $request,
        AthleteProgramAssignment $assignment,
        ProgramAiImporter $ai,
    ): JsonResponse {
        return $this->preview($request, $assignment, $ai);
    }

    /** @deprecated Use preview() */
    public function previewPhoto(
        PreviewProgramImportRequest $request,
        AthleteProgramAssignment $assignment,
        ProgramAiImporter $ai,
    ): JsonResponse {
        return $this->preview($request, $assignment, $ai);
    }

    public function apply(
        ApplyProgramImportRequest $request,
        AthleteProgramAssignment $assignment,
        BulkUpsertProgramSessionsAction $action,
    ): RedirectResponse {
        $this->authorize('manage', $assignment);
        $tab = $request->input('builder_tab', $request->query('tab'));

        $resolver = new ImportedExerciseResolver(
            $request->user(),
            new ExerciseNameMatcher($request->user()),
        );

        $createdMap = $resolver->createPending($request->input('exercises_to_create', []));
        $operations = $this->attachCreatedVariantIds(
            $request->input('operations', []),
            $createdMap,
        );

        $operations = array_map(static function (array $operation): array {
            $operation['items'] = array_map(static function (array $item): array {
                unset($item['will_create'], $item['parent_name'], $item['variant_name'], $item['category']);

                return $item;
            }, $operation['items'] ?? []);
            $operation['blocks'] = $operation['blocks'] ?? [];

            return $operation;
        }, $operations);

        (new EnsureProgramWeeksForImport)->ensure($assignment, $operations);

        $count = $action->execute(['operations' => $operations], $assignment);

        return redirect()
            ->route('program.builder', [
                'assignment' => $assignment->id,
                'tab' => is_string($tab) && in_array($tab, ['calendar', 'table', 'table_v2', 'stats'], true)
                    ? $tab
                    : 'table_v2',
            ])
            ->with('success', $count === 1
                ? '1 séance importée.'
                : "{$count} séances importées.");
    }

    /**
     * @param  list<array<string, mixed>>  $operations
     * @param  array<string, int>  $createdMap
     * @return list<array<string, mixed>>
     */
    private function attachCreatedVariantIds(array $operations, array $createdMap): array
    {
        foreach ($operations as &$operation) {
            foreach ($operation['items'] as &$item) {
                if (! empty($item['exercise_variant_id'])) {
                    continue;
                }
                $parent = (string) ($item['parent_name'] ?? '');
                $variant = (string) ($item['variant_name'] ?? $item['exercise_name'] ?? '');
                $key = mb_strtolower($parent).'|'.mb_strtolower($variant);
                if (isset($createdMap[$key])) {
                    $item['exercise_variant_id'] = $createdMap[$key];
                    $item['exercise_name'] = $variant !== '' ? $variant : $item['exercise_name'];
                }
            }
            unset($item);
        }
        unset($operation);

        return $operations;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildDraft($user, AthleteProgramAssignment $assignment, array $rows): array
    {
        $resolver = new ImportedExerciseResolver($user, new ExerciseNameMatcher($user));
        $builder = new ProgramImportDraftBuilder($resolver);

        return $builder->build($rows, $assignment);
    }
}
