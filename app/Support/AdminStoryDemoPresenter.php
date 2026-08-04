<?php

namespace App\Support;

class AdminStoryDemoPresenter
{
    /**
     * @return array{weekly: array<string, mixed>, monthly: array<string, mixed>}
     */
    public static function wrappedSamples(): array
    {
        $overview = [
            'total_tonnage' => ['value' => 12450, 'delta' => ['absolute' => 820, 'percent' => 7, 'direction' => 'up']],
            'adherence_percent' => ['value' => 100, 'delta' => ['absolute' => 0, 'percent' => 0, 'direction' => 'flat']],
            'total_sets' => ['value' => 86, 'delta' => ['absolute' => 6, 'percent' => 8, 'direction' => 'up']],
            'total_reps' => ['value' => 412, 'delta' => ['absolute' => 24, 'percent' => 6, 'direction' => 'up']],
            'tonnage_per_set' => ['value' => 145, 'delta' => ['absolute' => 5, 'percent' => 4, 'direction' => 'up']],
        ];

        $recap = [
            'heaviest_bar' => [
                'by_lift' => [
                    ['key' => 'squat', 'label' => 'Squat', 'value' => 160, 'delta' => ['absolute' => 5, 'percent' => 3, 'direction' => 'up']],
                    ['key' => 'bench', 'label' => 'Bench', 'value' => 110, 'delta' => ['absolute' => 2.5, 'percent' => 2, 'direction' => 'up']],
                    ['key' => 'deadlift', 'label' => 'Terre', 'value' => 200, 'delta' => ['absolute' => 10, 'percent' => 5, 'direction' => 'up']],
                ],
                'total' => ['value' => 470, 'delta' => ['absolute' => 17.5, 'percent' => 4, 'direction' => 'up']],
            ],
            'top_e1rm' => [
                'by_lift' => [
                    ['key' => 'squat', 'label' => 'Squat', 'value' => 178, 'delta' => ['absolute' => 4, 'percent' => 2, 'direction' => 'up']],
                    ['key' => 'bench', 'label' => 'Bench', 'value' => 122, 'delta' => ['absolute' => 3, 'percent' => 3, 'direction' => 'up']],
                    ['key' => 'deadlift', 'label' => 'Terre', 'value' => 215, 'delta' => ['absolute' => 8, 'percent' => 4, 'direction' => 'up']],
                ],
                'total' => ['value' => 515, 'delta' => ['absolute' => 15, 'percent' => 3, 'direction' => 'up']],
            ],
            'total_tonnage' => ['value' => 12450, 'delta' => ['absolute' => 820, 'percent' => 7, 'direction' => 'up']],
            'total_sets' => ['value' => 86, 'delta' => ['absolute' => 6, 'percent' => 8, 'direction' => 'up']],
            'total_training_minutes' => ['value' => 380, 'delta' => ['absolute' => 35, 'percent' => 10, 'direction' => 'up']],
        ];

        $lifts = [
            [
                'key' => 'squat',
                'label' => 'Squat',
                'heaviest_bar' => ['value' => 160, 'delta' => ['absolute' => 5, 'percent' => 3, 'direction' => 'up']],
                'tonnage' => ['value' => 4200, 'delta' => ['absolute' => 200, 'percent' => 5, 'direction' => 'up']],
                'top_e1rm' => ['value' => 178, 'delta' => ['absolute' => 4, 'percent' => 2, 'direction' => 'up']],
            ],
            [
                'key' => 'bench',
                'label' => 'Bench',
                'heaviest_bar' => ['value' => 110, 'delta' => ['absolute' => 2.5, 'percent' => 2, 'direction' => 'up']],
                'tonnage' => ['value' => 3100, 'delta' => ['absolute' => 150, 'percent' => 5, 'direction' => 'up']],
                'top_e1rm' => ['value' => 122, 'delta' => ['absolute' => 3, 'percent' => 3, 'direction' => 'up']],
            ],
            [
                'key' => 'deadlift',
                'label' => 'Deadlift',
                'heaviest_bar' => ['value' => 200, 'delta' => ['absolute' => 10, 'percent' => 5, 'direction' => 'up']],
                'tonnage' => ['value' => 5150, 'delta' => ['absolute' => 470, 'percent' => 10, 'direction' => 'up']],
                'top_e1rm' => ['value' => 215, 'delta' => ['absolute' => 8, 'percent' => 4, 'direction' => 'up']],
            ],
        ];

        $baseShare = [
            'athlete_name' => 'Alex Demo',
            'date' => now()->toDateString(),
            'share_url' => '/athlete/dashboard',
        ];

        return [
            'weekly' => [
                'label' => 'Weekly Wrapped',
                'variant' => 'weekly_wrapped',
                'comparison_label' => 'la semaine précédente',
                'period_start' => now()->startOfWeek()->toDateString(),
                'period_end' => now()->endOfWeek()->toDateString(),
                'session_count' => 4,
                'overview' => $overview,
                'lifts' => $lifts,
                'recap' => $recap,
                'share_payload' => array_merge($baseShare, [
                    'variant' => 'weekly_wrapped',
                    'headline' => 'Weekly Wrapped',
                    'subline' => '412 reps · 86 séries · 12450 kg',
                    'social_text' => 'Alex Demo · Weekly Wrapped',
                ]),
            ],
            'monthly' => [
                'label' => 'Monthly Wrapped',
                'variant' => 'monthly_wrapped',
                'comparison_label' => 'le mois précédent',
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'session_count' => 16,
                'overview' => $overview,
                'lifts' => $lifts,
                'recap' => $recap,
                'share_payload' => array_merge($baseShare, [
                    'variant' => 'monthly_wrapped',
                    'headline' => 'Monthly Wrapped',
                    'subline' => '412 reps · 86 séries · 12450 kg',
                    'social_text' => 'Alex Demo · Monthly Wrapped',
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $copy
     * @return array<string, mixed>
     */
    public static function awardsSample(array $copy = []): array
    {
        $steps = $copy['steps'] ?? [];
        $kcal = $copy['kcal'] ?? [];
        $sommeil = $copy['sommeil'] ?? [];

        $name = 'Camille';

        return [
            'label' => 'Monthly Roster Awards',
            'variant' => 'coach_monthly_readiness_awards',
            'month_label' => now()->locale(app()->getLocale())->translatedFormat('F Y'),
            'screens' => [
                [
                    'id' => 'most_steps',
                    'kind' => 'roster_award',
                    'award_key' => 'steps',
                    'eyebrow' => $steps['eyebrow'] ?? 'Hall of Fame · Pas',
                    'title' => $steps['title'] ?? 'Le podomètre a fondu',
                    'punchline' => str_replace('{name}', $name, $steps['punchline'] ?? '{name} a fait marcher le groupe… littéralement.'),
                    'athlete_name' => $name,
                    'value_label' => '86 420 pas',
                    'footnote' => 'Cumul du mois · 22 jours saisis',
                ],
                [
                    'id' => 'most_kcal',
                    'kind' => 'roster_award',
                    'award_key' => 'kcal',
                    'eyebrow' => $kcal['eyebrow'] ?? 'Hall of Fame · Assiette',
                    'title' => $kcal['title'] ?? 'Le roi / la reine de la fourchette',
                    'punchline' => str_replace('{name}', 'Jordan', $kcal['punchline'] ?? '{name} a mis le plus de carburant dans le réservoir.'),
                    'athlete_name' => 'Jordan',
                    'value_label' => '64 200 kcal',
                    'footnote' => 'Cumul estimé du mois · 20 jours saisis',
                ],
                [
                    'id' => 'biggest_sleeper',
                    'kind' => 'roster_award',
                    'award_key' => 'sommeil',
                    'eyebrow' => $sommeil['eyebrow'] ?? 'Hall of Fame · Oreiller',
                    'title' => $sommeil['title'] ?? 'Le plus gros dormeur',
                    'punchline' => str_replace('{name}', 'Sam', $sommeil['punchline'] ?? '{name} collectionne les heures de lit comme des médailles.'),
                    'athlete_name' => 'Sam',
                    'value_label' => 'Souvent 8-9H',
                    'footnote' => 'Meilleure moyenne sommeil du mois · 18 nuits',
                ],
            ],
        ];
    }
}
