<?php

namespace App\Support;

use App\Models\ProgramDayExercise;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;

class ProgramSessionSerializer
{
    /**
     * @return array<string, mixed>
     */
    public static function trainingDayToPayload(ProgramTrainingDay $day): array
    {
        $day->loadMissing(['exercises.exerciseVariant.exercise', 'week.template']);

        $override = (bool) $day->warmup_override;
        $warmup = self::resolveWarmup($day, $day->week?->template);

        $items = self::itemsFromExercises($day->exercises);
        if (! $override) {
            $items = array_values(array_filter(
                $items,
                static fn (array $item): bool => ($item['section'] ?? null) !== ProgramDayExercise::SECTION_WARMUP,
            ));
        }

        return [
            'id' => $day->id,
            'week_number' => $day->week->week_number,
            'weekday' => $day->day_number,
            'main_lift' => $day->main_lift,
            'session_label' => $day->session_label,
            'notes' => $day->notes,
            'warmup_override' => $override,
            'warmup_notes' => $override ? $day->warmup_notes : null,
            'warmup' => $warmup,
            'items' => $items,
            'blocks' => self::blocksFromExercises($day->exercises),
        ];
    }

    /**
     * @return array{notes: ?string, items: list<array<string, mixed>>, source: string}
     */
    public static function resolveWarmup(ProgramTrainingDay $day, ?ProgramTemplate $template = null): array
    {
        $template ??= $day->week?->template;

        if ($day->warmup_override) {
            $items = [];
            foreach ($day->exercises as $line) {
                if ($line->section !== ProgramDayExercise::SECTION_WARMUP) {
                    continue;
                }
                $items[] = self::lineToPayload($line);
            }

            return [
                'notes' => $day->warmup_notes,
                'items' => $items,
                'source' => 'session',
            ];
        }

        $notes = $template?->default_warmup_notes;
        $rawItems = $template?->default_warmup_items ?? [];
        $items = [];

        if (is_array($rawItems)) {
            foreach ($rawItems as $item) {
                if (! is_array($item) || empty(trim((string) ($item['exercise_name'] ?? '')))) {
                    continue;
                }
                $items[] = [
                    'exercise_variant_id' => $item['exercise_variant_id'] ?? null,
                    'exercise_name' => $item['exercise_name'],
                    'lift' => $item['lift'] ?? null,
                    'sets' => $item['sets'] ?? null,
                    'reps' => $item['reps'] ?? null,
                    'load' => $item['load'] ?? null,
                    'load_percent' => $item['load_percent'] ?? null,
                    'rpe' => $item['rpe'] ?? null,
                    'rest_seconds' => $item['rest_seconds'] ?? null,
                ];
            }
        }

        return [
            'notes' => is_string($notes) && trim($notes) !== '' ? $notes : null,
            'items' => $items,
            'source' => 'block',
        ];
    }

    /**
     * @param  iterable<int, ProgramDayExercise>  $exercises
     * @return list<array<string, mixed>>
     */
    public static function itemsFromExercises(iterable $exercises): array
    {
        $items = [];

        foreach ($exercises as $line) {
            $items[] = array_merge(
                ['section' => $line->section],
                self::lineToPayload($line),
            );
        }

        return $items;
    }

    /**
     * @param  iterable<int, ProgramDayExercise>  $exercises
     * @return list<array<string, mixed>>
     */
    public static function blocksFromExercises(iterable $exercises): array
    {
        $grouped = [];

        foreach ($exercises as $line) {
            if ($line->section === ProgramDayExercise::SECTION_WARMUP) {
                continue;
            }

            $index = (int) $line->block_index;
            if (! isset($grouped[$index])) {
                $grouped[$index] = [
                    'lift' => $line->lift ?? 'squat',
                    'topset' => null,
                    'backoff' => null,
                    'accessories' => [],
                ];
            }

            $payload = self::lineToPayload($line);

            match ($line->section) {
                ProgramDayExercise::SECTION_TOPSET => $grouped[$index]['topset'] = $payload,
                ProgramDayExercise::SECTION_BACKOFF => $grouped[$index]['backoff'] = $payload,
                ProgramDayExercise::SECTION_ACCESSORY => $grouped[$index]['accessories'][] = $payload,
                default => null,
            };

            if ($line->lift && in_array($line->lift, ['squat', 'bench', 'deadlift'], true)) {
                $grouped[$index]['lift'] = $line->lift;
            }
        }

        ksort($grouped);

        return array_values($grouped);
    }

    /**
     * @return array<string, mixed>
     */
    private static function lineToPayload(ProgramDayExercise $line): array
    {
        return [
            'exercise_variant_id' => $line->exercise_variant_id,
            'exercise_name' => $line->exercise_name,
            'lift' => $line->lift,
            'sets' => $line->sets,
            'reps' => $line->reps,
            'load' => $line->load,
            'load_percent' => $line->load_percent,
            'rpe' => $line->rpe,
            'rest_seconds' => $line->rest_seconds,
            'movement_pattern' => $line->exerciseVariant?->exercise?->movement_pattern,
        ];
    }

    /**
     * @param  array<string, mixed>  $dayData
     */
    public static function persistExercises(ProgramTrainingDay $day, array $dayData): void
    {
        $day->exercises()->delete();

        $warmupOverride = (bool) ($dayData['warmup_override'] ?? $day->warmup_override);
        $items = $dayData['items'] ?? null;

        if (is_array($items) && $items !== []) {
            $sortOrder = 0;
            $defaultLift = $dayData['main_lift'] ?? 'squat';

            foreach ($items as $item) {
                if (empty($item['exercise_name'])) {
                    continue;
                }

                $section = $item['section'] ?? ProgramDayExercise::SECTION_ACCESSORY;

                if ($section === ProgramDayExercise::SECTION_WARMUP && ! $warmupOverride) {
                    continue;
                }

                self::createLine(
                    $day->id,
                    $section,
                    $item,
                    $sortOrder++,
                    0,
                    $item['lift'] ?? $defaultLift,
                );
            }

            return;
        }

        $blocks = $dayData['blocks'] ?? null;

        if ($blocks === null && ! empty($dayData['topset'])) {
            $blocks = [[
                'lift' => $dayData['main_lift'] ?? 'squat',
                'topset' => $dayData['topset'] ?? null,
                'backoff' => $dayData['backoff'] ?? null,
                'accessories' => $dayData['accessories'] ?? [],
            ]];
        }

        $sortOrder = 0;

        foreach ($blocks ?? [] as $blockIndex => $block) {
            $lift = $block['lift'] ?? 'squat';

            if (! empty($block['topset'])) {
                self::createLine(
                    $day->id,
                    ProgramDayExercise::SECTION_TOPSET,
                    $block['topset'],
                    $sortOrder++,
                    $blockIndex,
                    $lift,
                );
            }

            if (! empty($block['backoff'])) {
                self::createLine(
                    $day->id,
                    ProgramDayExercise::SECTION_BACKOFF,
                    $block['backoff'],
                    $sortOrder++,
                    $blockIndex,
                    $lift,
                );
            }

            foreach ($block['accessories'] ?? [] as $accessory) {
                self::createLine(
                    $day->id,
                    ProgramDayExercise::SECTION_ACCESSORY,
                    $accessory,
                    $sortOrder++,
                    $blockIndex,
                    $lift,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private static function createLine(
        int $trainingDayId,
        string $section,
        array $line,
        int $sortOrder,
        int $blockIndex,
        string $lift,
    ): void {
        ProgramDayExercise::create([
            'training_day_id' => $trainingDayId,
            'block_index' => $blockIndex,
            'lift' => $line['lift'] ?? $lift,
            'exercise_variant_id' => $line['exercise_variant_id'] ?? null,
            'section' => $section,
            'exercise_name' => $line['exercise_name'],
            'sets' => $line['sets'],
            'reps' => $line['reps'],
            'load' => $line['load'] ?? null,
            'load_percent' => $line['load_percent'] ?? null,
            'rpe' => $line['rpe'] ?? null,
            'rest_seconds' => $line['rest_seconds'] ?? null,
            'sort_order' => $sortOrder,
        ]);
    }
}
