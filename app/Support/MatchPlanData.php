<?php

namespace App\Support;

class MatchPlanData
{
    public const LIFTS = ['squat', 'bench', 'deadlift'];

    public const LIFT_LABELS = [
        'squat' => 'Squat',
        'bench' => 'Bench',
        'deadlift' => 'Deadlift',
    ];

    public const MAX_WARMUP_BARS = 10;

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function toText(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        $mode = $data['mode'] ?? 'text';
        $lines = [];

        if ($mode === 'text') {
            $text = trim((string) ($data['text'] ?? ''));
            if ($text !== '') {
                $lines[] = $text;
            }
        } elseif ($mode === 'structured') {
            $scenarios = $data['scenarios'] ?? [];
            foreach ($scenarios as $scenario) {
                $name = trim((string) ($scenario['name'] ?? 'Scénario'));
                $lines[] = $name;

                foreach (self::LIFTS as $lift) {
                    $attempts = $scenario['lifts'][$lift] ?? [];
                    $parts = [];
                    foreach (['attempt1', 'attempt2', 'attempt3'] as $key) {
                        $value = $attempts[$key] ?? null;
                        if ($value !== null && $value !== '') {
                            $parts[] = self::formatWeight($value);
                        }
                    }
                    if ($parts !== []) {
                        $lines[] = self::LIFT_LABELS[$lift].' : '.implode(' / ', $parts);
                    }
                }

                $total = self::scenarioTotal($scenario);
                if ($total > 0) {
                    $lines[] = 'Total visé (3e essais) : '.self::formatWeight($total).' kg';
                }

                $lines[] = '';
            }
        }

        $warmupLines = self::warmupLines($data['warmups'] ?? null);
        if ($warmupLines !== []) {
            if ($lines !== []) {
                $lines[] = '';
            }
            $lines[] = 'Barres d’échauffement';
            foreach ($warmupLines as $line) {
                $lines[] = $line;
            }
        }

        $text = trim(implode("\n", $lines));

        return $text !== '' ? $text : null;
    }

    /**
     * @param  array<string, mixed>|null  $warmups
     * @return list<string>
     */
    private static function warmupLines(?array $warmups): array
    {
        if ($warmups === null) {
            return [];
        }

        $lines = [];
        foreach (self::LIFTS as $lift) {
            $bars = array_values(array_filter(
                array_map(fn ($v) => self::nullableWeight($v), $warmups[$lift] ?? []),
                fn ($v) => $v !== null,
            ));
            if ($bars === []) {
                continue;
            }
            $lines[] = self::LIFT_LABELS[$lift].' : '.implode(' / ', array_map(
                fn ($v) => self::formatWeight($v),
                $bars,
            )).' kg';
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    public static function scenarioTotal(array $scenario): float
    {
        $sum = 0.0;
        $has = false;

        foreach (self::LIFTS as $lift) {
            $value = $scenario['lifts'][$lift]['attempt3'] ?? null;
            if ($value !== null && $value !== '') {
                $sum += (float) $value;
                $has = true;
            }
        }

        return $has ? $sum : 0.0;
    }

    public static function formatWeight(mixed $value): string
    {
        $n = (float) $value;

        if (abs($n - round($n)) < 0.001) {
            return (string) (int) round($n);
        }

        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function normalize(?array $data): array
    {
        if ($data === null || $data === []) {
            return self::defaultStructured();
        }

        $mode = $data['mode'] ?? 'text';
        $warmups = self::normalizeWarmups($data['warmups'] ?? null);

        if ($mode === 'text') {
            return [
                'mode' => 'text',
                'text' => (string) ($data['text'] ?? ''),
                'warmups' => $warmups,
            ];
        }

        $scenarios = [];
        foreach ($data['scenarios'] ?? [] as $scenario) {
            if (! is_array($scenario)) {
                continue;
            }
            $scenarios[] = self::normalizeScenario($scenario);
        }

        if ($scenarios === []) {
            $scenarios[] = self::emptyScenario('Scénario principal');
        }

        return [
            'mode' => 'structured',
            'scenarios' => $scenarios,
            'warmups' => $warmups,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{squat: list<float>, bench: list<float>, deadlift: list<float>}
     */
    public static function normalizeWarmups(?array $raw): array
    {
        $out = self::emptyWarmups();
        if ($raw === null) {
            return $out;
        }

        foreach (self::LIFTS as $lift) {
            $bars = [];
            foreach ($raw[$lift] ?? [] as $value) {
                $weight = self::nullableWeight($value);
                if ($weight !== null) {
                    $bars[] = $weight;
                }
                if (count($bars) >= self::MAX_WARMUP_BARS) {
                    break;
                }
            }
            $out[$lift] = $bars;
        }

        return $out;
    }

    /**
     * @return array{squat: list<float>, bench: list<float>, deadlift: list<float>}
     */
    public static function emptyWarmups(): array
    {
        return [
            'squat' => [],
            'bench' => [],
            'deadlift' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultStructured(): array
    {
        return [
            'mode' => 'structured',
            'scenarios' => [self::emptyScenario('Scénario principal')],
            'warmups' => self::emptyWarmups(),
        ];
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public static function normalizeScenario(array $scenario): array
    {
        $lifts = [];
        foreach (self::LIFTS as $lift) {
            $raw = $scenario['lifts'][$lift] ?? [];
            $lifts[$lift] = [
                'attempt1' => self::nullableWeight($raw['attempt1'] ?? null),
                'attempt2' => self::nullableWeight($raw['attempt2'] ?? null),
                'attempt3' => self::nullableWeight($raw['attempt3'] ?? null),
            ];
        }

        return [
            'id' => (string) ($scenario['id'] ?? uniqid('sc_', true)),
            'name' => trim((string) ($scenario['name'] ?? 'Scénario')) ?: 'Scénario',
            'lifts' => $lifts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyScenario(string $name = 'Scénario'): array
    {
        $lifts = [];
        foreach (self::LIFTS as $lift) {
            $lifts[$lift] = [
                'attempt1' => null,
                'attempt2' => null,
                'attempt3' => null,
            ];
        }

        return [
            'id' => uniqid('sc_', true),
            'name' => $name,
            'lifts' => $lifts,
        ];
    }

    private static function nullableWeight(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function hasContent(?array $data, ?string $legacyText = null): bool
    {
        if ($data !== null && $data !== []) {
            $mode = $data['mode'] ?? 'structured';

            if ($mode === 'text' && trim((string) ($data['text'] ?? '')) !== '') {
                return true;
            }

            foreach ($data['scenarios'] ?? [] as $scenario) {
                if (! is_array($scenario)) {
                    continue;
                }

                foreach (self::LIFTS as $lift) {
                    foreach (['attempt1', 'attempt2', 'attempt3'] as $key) {
                        $value = $scenario['lifts'][$lift][$key] ?? null;
                        if ($value !== null && $value !== '') {
                            return true;
                        }
                    }
                }
            }

            foreach (self::LIFTS as $lift) {
                foreach ($data['warmups'][$lift] ?? [] as $value) {
                    if (self::nullableWeight($value) !== null) {
                        return true;
                    }
                }
            }

            return false;
        }

        return trim((string) ($legacyText ?? '')) !== '';
    }
}
