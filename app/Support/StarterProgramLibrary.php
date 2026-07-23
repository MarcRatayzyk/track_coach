<?php

namespace App\Support;

class StarterProgramLibrary
{
    /**
     * Ready-to-use starter programs a coach can materialize in one click.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::fullBodyBeginner(),
            self::strength5x5(),
            self::peakingMeet(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $program) {
            if ($program['key'] === $key) {
                return $program;
            }
        }

        return null;
    }

    /**
     * Lightweight catalog (no week detail) for listing in the UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function catalog(): array
    {
        return array_map(static fn (array $p): array => [
            'key' => $p['key'],
            'name' => $p['name'],
            'goal' => $p['goal'],
            'level' => $p['level'],
            'week_count' => count($p['weeks']),
            'summary' => $p['summary'],
        ], self::all());
    }

    /**
     * @return array<string, mixed>
     */
    private static function fullBodyBeginner(): array
    {
        $day = static fn (int $n, string $lift, string $label, array $exercises): array => [
            'day_number' => $n,
            'main_lift' => $lift,
            'session_label' => $label,
            'exercises' => $exercises,
        ];

        $week = static fn (int $n, array $days): array => [
            'week_number' => $n,
            'block_type' => 'volume',
            'days' => $days,
        ];

        $ex = static fn (string $name, int $sets, int $reps, ?float $rpe = null, string $section = 'topset', string $lift = 'squat'): array => [
            'section' => $section,
            'lift' => $lift,
            'exercise_name' => $name,
            'sets' => $sets,
            'reps' => $reps,
            'rpe' => $rpe,
        ];

        $days = [
            $day(1, 'squat', 'Full body A', [
                $ex('Squat', 3, 5, 7, 'topset', 'squat'),
                $ex('Développé couché', 3, 5, 7, 'topset', 'bench'),
                $ex('Rowing barre', 3, 8, null, 'accessory', 'bench'),
            ]),
            $day(2, 'deadlift', 'Full body B', [
                $ex('Soulevé de terre', 2, 5, 7, 'topset', 'deadlift'),
                $ex('Développé militaire', 3, 6, null, 'topset', 'bench'),
                $ex('Tractions', 3, 8, null, 'accessory', 'bench'),
            ]),
            $day(3, 'squat', 'Full body C', [
                $ex('Squat', 3, 5, 7, 'topset', 'squat'),
                $ex('Développé couché', 3, 5, 7, 'topset', 'bench'),
                $ex('Gainage', 3, 30, null, 'accessory', 'squat'),
            ]),
        ];

        return [
            'key' => 'full-body-beginner',
            'name' => 'Débutant full body 3 jours',
            'goal' => 'Apprentissage technique et progression linéaire',
            'level' => 'beginner',
            'summary' => '4 semaines, 3 séances full body par semaine, focus technique SBD.',
            'weeks' => array_map(static fn (int $n): array => $week($n, $days), range(1, 4)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function strength5x5(): array
    {
        $ex = static fn (string $name, int $sets, int $reps, string $lift, string $section = 'topset'): array => [
            'section' => $section,
            'lift' => $lift,
            'exercise_name' => $name,
            'sets' => $sets,
            'reps' => $reps,
            'rpe' => null,
        ];

        $days = [
            [
                'day_number' => 1,
                'main_lift' => 'squat',
                'session_label' => 'Séance A',
                'exercises' => [
                    $ex('Squat', 5, 5, 'squat'),
                    $ex('Développé couché', 5, 5, 'bench'),
                    $ex('Rowing barre', 5, 5, 'bench', 'accessory'),
                ],
            ],
            [
                'day_number' => 2,
                'main_lift' => 'squat',
                'session_label' => 'Séance B',
                'exercises' => [
                    $ex('Squat', 5, 5, 'squat'),
                    $ex('Développé militaire', 5, 5, 'bench'),
                    $ex('Soulevé de terre', 1, 5, 'deadlift'),
                ],
            ],
            [
                'day_number' => 3,
                'main_lift' => 'squat',
                'session_label' => 'Séance A',
                'exercises' => [
                    $ex('Squat', 5, 5, 'squat'),
                    $ex('Développé couché', 5, 5, 'bench'),
                    $ex('Rowing barre', 5, 5, 'bench', 'accessory'),
                ],
            ],
        ];

        return [
            'key' => 'strength-5x5',
            'name' => 'Force 5x5',
            'goal' => 'Développement de la force de base',
            'level' => 'intermediate',
            'summary' => '5 semaines, 3 séances 5x5 en alternance A/B, progression en charge.',
            'weeks' => array_map(static fn (int $n): array => [
                'week_number' => $n,
                'block_type' => $n <= 3 ? 'volume' : 'intensification',
                'days' => $days,
            ], range(1, 5)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function peakingMeet(): array
    {
        $topset = static fn (string $lift, int $reps, float $pct): array => [
            'section' => 'topset',
            'lift' => $lift,
            'exercise_name' => ucfirst($lift),
            'sets' => 1,
            'reps' => $reps,
            'load_percent' => $pct,
            'rpe' => null,
        ];

        $weekPlan = [
            1 => ['type' => 'volume', 'reps' => 5, 'pct' => 75.0],
            2 => ['type' => 'volume', 'reps' => 4, 'pct' => 80.0],
            3 => ['type' => 'intensification', 'reps' => 3, 'pct' => 85.0],
            4 => ['type' => 'intensification', 'reps' => 2, 'pct' => 90.0],
            5 => ['type' => 'peaking', 'reps' => 1, 'pct' => 95.0],
            6 => ['type' => 'peaking', 'reps' => 1, 'pct' => 100.0],
        ];

        $weeks = [];
        foreach ($weekPlan as $number => $plan) {
            $weeks[] = [
                'week_number' => $number,
                'block_type' => $plan['type'],
                'days' => [
                    [
                        'day_number' => 1,
                        'main_lift' => 'squat',
                        'session_label' => 'Squat',
                        'exercises' => [$topset('squat', $plan['reps'], $plan['pct'])],
                    ],
                    [
                        'day_number' => 2,
                        'main_lift' => 'bench',
                        'session_label' => 'Bench',
                        'exercises' => [$topset('bench', $plan['reps'], $plan['pct'])],
                    ],
                    [
                        'day_number' => 3,
                        'main_lift' => 'deadlift',
                        'session_label' => 'Deadlift',
                        'exercises' => [$topset('deadlift', $plan['reps'], $plan['pct'])],
                    ],
                ],
            ];
        }

        return [
            'key' => 'peaking-meet-6w',
            'name' => 'Peaking compétition 6 semaines',
            'goal' => 'Amener le total au pic pour une compétition',
            'level' => 'advanced',
            'summary' => '6 semaines, montée en intensité SBD de 75% à 100%.',
            'weeks' => $weeks,
        ];
    }
}
