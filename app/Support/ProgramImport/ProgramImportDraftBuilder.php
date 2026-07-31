<?php

namespace App\Support\ProgramImport;

use App\Models\AthleteProgramAssignment;

class ProgramImportDraftBuilder
{
    public function __construct(
        private readonly ImportedExerciseResolver $resolver,
        private readonly ProgramChargeParser $chargeParser = new ProgramChargeParser,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *   operations: list<array<string, mixed>>,
     *   warnings: list<string>,
     *   unmatched_exercises: list<string>,
     *   exercises_to_create: list<array<string, mixed>>,
     *   session_count: int,
     *   exercise_count: int
     * }
     */
    public function build(array $rows, AthleteProgramAssignment $assignment): array
    {
        $assignment->loadMissing('template.weeks');
        $validWeeks = $assignment->template?->weeks
            ->pluck('week_number')
            ->map(fn ($n) => (int) $n)
            ->all() ?? [];

        $warnings = [];
        $unmatched = [];
        $toCreate = [];
        $grouped = [];

        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $week = $this->intOrNull($row[ProgramCsvColumns::WEEK] ?? null);
            $day = $this->intOrNull($row[ProgramCsvColumns::DAY] ?? null);
            $variantRaw = trim((string) ($row['variant_name'] ?? $row[ProgramCsvColumns::EXERCISE] ?? ''));
            $parentRaw = trim((string) ($row['parent_name'] ?? ''));

            // Prefer exact cell text from the model (sets_raw/reps_raw) over numeric guesses.
            $setsSource = $row['sets_raw'] ?? $row[ProgramCsvColumns::SETS] ?? null;
            $repsSource = $row['reps_raw'] ?? $row[ProgramCsvColumns::REPS] ?? null;
            $sets = $this->chargeParser->parseIntRange($setsSource, 0);
            $reps = $this->chargeParser->parseIntRange($repsSource, 0);

            if ($week === null || $day === null || $variantRaw === '' || $sets < 1 || $reps < 1) {
                $warnings[] = "Ligne {$line} : semaine, jour, exercice, séries et reps sont requis — ligne ignorée.";
                continue;
            }

            if ($day < 1 || $day > 7) {
                $warnings[] = "Ligne {$line} : jour {$day} invalide (1–7) — ligne ignorée.";
                continue;
            }

            if ($validWeeks !== [] && ! in_array($week, $validWeeks, true)) {
                $warnings[] = "Semaine {$week} absente du bloc actuel — elle sera ajoutée à l’import.";
            }

            $sets = min(10, max(1, $sets));
            $reps = min(200, max(1, $reps));

            $resolved = $this->resolver->resolve(
                $variantRaw,
                $parentRaw !== '' ? $parentRaw : null,
                isset($row[ProgramCsvColumns::MAIN_LIFT]) ? (string) $row[ProgramCsvColumns::MAIN_LIFT] : ($row['lift'] ?? null),
                isset($row['category']) ? (string) $row['category'] : null,
            );

            if ($resolved['will_create']) {
                $unmatched[] = $resolved['variant_name'];
                $createKey = mb_strtolower(($resolved['parent_name'] ?? '').'|'.$resolved['variant_name']);
                $toCreate[$createKey] = [
                    'parent_name' => $resolved['parent_name'],
                    'variant_name' => $resolved['variant_name'],
                    'lift' => $resolved['lift'],
                    'category' => $resolved['category'],
                ];
            }

            $section = $this->normalizeSection((string) ($row[ProgramCsvColumns::SECTION] ?? ''));
            $mainLift = $this->normalizeLift((string) ($row[ProgramCsvColumns::MAIN_LIFT] ?? ''))
                ?? $resolved['lift']
                ?? 'squat';
            if (! in_array($mainLift, ['squat', 'bench', 'deadlift'], true)) {
                $mainLift = 'squat';
            }

            $chargeRaw = trim((string) ($row['charge_raw'] ?? ''));
            $charge = $this->chargeParser->parse(
                $chargeRaw !== '' ? $chargeRaw : ($row[ProgramCsvColumns::LOAD] ?? null),
            );

            // When charge_raw is present, trust the local parser over AI numeric fields
            // (AI often mis-converts 167,5kg → 160 or 70%RM → load kg).
            if ($chargeRaw !== '') {
                $load = $charge['load'];
                $loadPercent = $charge['load_percent'];
                $rpe = $charge['rpe'];
            } else {
                $load = $this->floatOrNull($row[ProgramCsvColumns::LOAD] ?? null) ?? $charge['load'];
                $loadPercent = $this->floatOrNull($row[ProgramCsvColumns::LOAD_PERCENT] ?? null) ?? $charge['load_percent'];
                $rpe = $this->floatOrNull($row[ProgramCsvColumns::RPE] ?? null) ?? $charge['rpe'];
            }
            $rest = $this->chargeParser->parseRestSeconds($row[ProgramCsvColumns::REST_SECONDS] ?? null);

            $itemLift = in_array($resolved['lift'], ['squat', 'bench', 'deadlift'], true)
                ? $resolved['lift']
                : (($section === 'topset' || $section === 'backoff') ? $mainLift : null);

            $notes = $this->nullableString($row[ProgramCsvColumns::NOTES] ?? null);
            if ($charge['notes'] !== null) {
                $notes = trim(($notes ? $notes.' · ' : '').$charge['notes']);
            }

            $key = "{$week}-{$day}";
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'week_number' => $week,
                    'weekday' => $day,
                    'main_lift' => $mainLift,
                    'session_label' => $this->nullableString($row[ProgramCsvColumns::SESSION_LABEL] ?? null) ?? "Jour {$day}",
                    'notes' => $notes,
                    'items' => [],
                    'blocks' => [],
                ];
            } else {
                $label = $this->nullableString($row[ProgramCsvColumns::SESSION_LABEL] ?? null);
                if ($label !== null) {
                    $grouped[$key]['session_label'] = $label;
                }
                $liftFromRow = $this->normalizeLift((string) ($row[ProgramCsvColumns::MAIN_LIFT] ?? ''));
                if ($liftFromRow !== null && in_array($liftFromRow, ['squat', 'bench', 'deadlift'], true)) {
                    $grouped[$key]['main_lift'] = $liftFromRow;
                }
            }

            $grouped[$key]['items'][] = [
                'section' => $section,
                'exercise_variant_id' => $resolved['exercise_variant_id'],
                'exercise_name' => $resolved['exercise_name'],
                'lift' => $itemLift,
                'set_scheme' => 'standard',
                'scheme_config' => null,
                'sets' => $sets,
                'reps' => $reps,
                'load' => $load,
                'load_percent' => $loadPercent,
                'rpe' => $rpe,
                'rest_seconds' => $rest,
                'will_create' => $resolved['will_create'],
                'parent_name' => $resolved['parent_name'],
                'variant_name' => $resolved['variant_name'],
                'category' => $resolved['category'],
            ];
        }

        $operations = array_values($grouped);
        $exerciseCount = array_sum(array_map(
            static fn (array $op): int => count($op['items']),
            $operations,
        ));

        return [
            'operations' => $operations,
            'warnings' => array_values(array_unique($warnings)),
            'unmatched_exercises' => array_values(array_unique($unmatched)),
            'exercises_to_create' => array_values($toCreate),
            'session_count' => count($operations),
            'exercise_count' => $exerciseCount,
        ];
    }

    private function normalizeSection(string $raw): string
    {
        $value = mb_strtolower(trim($raw));
        $map = [
            'topset' => 'topset',
            'top set' => 'topset',
            'top' => 'topset',
            'principal' => 'topset',
            'backoff' => 'backoff',
            'back off' => 'backoff',
            'back-off' => 'backoff',
            'volume' => 'backoff',
            'accessory' => 'accessory',
            'accessoire' => 'accessory',
            'assistance' => 'accessory',
            'warmup' => 'warmup',
            'warm-up' => 'warmup',
            'échauffement' => 'warmup',
            'echauffement' => 'warmup',
        ];

        return $map[$value] ?? 'accessory';
    }

    private function normalizeLift(string $raw): ?string
    {
        $value = mb_strtolower(trim($raw));
        if ($value === '') {
            return null;
        }

        $map = [
            'squat' => 'squat',
            'sq' => 'squat',
            'bench' => 'bench',
            'bench press' => 'bench',
            'bp' => 'bench',
            'dc' => 'bench',
            'développé' => 'bench',
            'developpe' => 'bench',
            'deadlift' => 'deadlift',
            'dead' => 'deadlift',
            'dl' => 'deadlift',
            'sdt' => 'deadlift',
            'soulevé' => 'deadlift',
            'souleve' => 'deadlift',
        ];

        return $map[$value] ?? (in_array($value, ['squat', 'bench', 'deadlift'], true) ? $value : null);
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value) && preg_match('/-?\d+/', $value, $m)) {
            return (int) $m[0];
        }

        return null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(['%', ','], ['', '.'], trim($value));
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
