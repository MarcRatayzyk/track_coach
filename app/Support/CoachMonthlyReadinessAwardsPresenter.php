<?php

namespace App\Support;

use App\Models\AthleteReadinessEntry;
use App\Models\AthleteReadinessForm;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CoachMonthlyReadinessAwardsPresenter
{
    private const SLEEP_ORDINAL = [
        'lt_5h' => 1,
        '5_6h' => 2,
        '6_7h' => 3,
        '7_8h' => 4,
        '8_9h' => 5,
    ];

    private const SLEEP_LABELS = [
        'lt_5h' => '- 5H',
        '5_6h' => '5-6H',
        '6_7h' => '6-7H',
        '7_8h' => '7-8H',
        '8_9h' => '8-9H',
    ];

    /**
     * Monthly humour awards for the coach roster.
     * Only includes metrics present in the coach readiness template.
     *
     * @return array<string, mixed>|null
     */
    public function forCoach(User $coach, ?CarbonInterface $date = null): ?array
    {
        $today = ($date ?? now())->copy()->startOfDay();
        $start = $today->copy()->startOfMonth();
        $end = $today->copy()->endOfMonth();

        $coachForm = ReadinessFormSupport::ensureCoachHasDefaultForm($coach);
        $enabledPresets = $this->enabledAwardPresets($coachForm->fields ?? []);

        if ($enabledPresets === []) {
            return null;
        }

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->get(['users.id', 'users.name']);

        if ($athletes->isEmpty()) {
            return null;
        }

        $formsByAthlete = AthleteReadinessForm::query()
            ->whereIn('athlete_id', $athletes->pluck('id'))
            ->get()
            ->keyBy('athlete_id');

        $entries = AthleteReadinessEntry::query()
            ->whereIn('athlete_id', $athletes->pluck('id'))
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->whereDate('entry_date', '<=', min($today->toDateString(), $end->toDateString()))
            ->get()
            ->groupBy('athlete_id');

        $screens = [];

        if (isset($enabledPresets['steps'])) {
            $winner = $this->winnerForNumericSum($athletes, $formsByAthlete, $entries, 'steps');
            if ($winner !== null) {
                $screens[] = $this->stepsScreen($winner);
            }
        }

        if (isset($enabledPresets['kcal'])) {
            $winner = $this->winnerForKcalSum($athletes, $formsByAthlete, $entries);
            if ($winner !== null) {
                $screens[] = $this->kcalScreen($winner);
            }
        }

        if (isset($enabledPresets['sommeil'])) {
            $winner = $this->winnerForSleep($athletes, $formsByAthlete, $entries);
            if ($winner !== null) {
                $screens[] = $this->sleepScreen($winner);
            }
        }

        if ($screens === []) {
            return null;
        }

        $monthLabel = $start->locale('fr')->translatedFormat('F Y');

        return [
            'label' => 'Monthly Roster Awards',
            'variant' => 'coach_monthly_readiness_awards',
            'period_start' => $start->toDateString(),
            'period_end' => min($today->toDateString(), $end->toDateString()),
            'month_label' => ucfirst($monthLabel),
            'screens' => $screens,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|mixed  $fields
     * @return array<string, true>
     */
    private function enabledAwardPresets(mixed $fields): array
    {
        $enabled = [];

        foreach (ReadinessFormSupport::normalizeFields($fields) as $field) {
            $key = $field['preset_key'] ?? null;
            if (in_array($key, ['steps', 'kcal', 'sommeil'], true)) {
                $enabled[$key] = true;
            }
        }

        return $enabled;
    }

    /**
     * @param  Collection<int, User>  $athletes
     * @param  Collection<int, AthleteReadinessForm>  $formsByAthlete
     * @param  Collection<int, Collection<int, AthleteReadinessEntry>>  $entries
     * @return array{athlete_id: int, athlete_name: string, value: float|int, display_value: string, days: int}|null
     */
    private function winnerForNumericSum(
        Collection $athletes,
        Collection $formsByAthlete,
        Collection $entries,
        string $presetKey,
    ): ?array {
        $best = null;

        foreach ($athletes as $athlete) {
            $fieldId = $this->fieldIdForPreset($formsByAthlete->get($athlete->id), $presetKey);
            if ($fieldId === null) {
                continue;
            }

            $sum = 0.0;
            $days = 0;

            foreach ($entries->get($athlete->id, collect()) as $entry) {
                $raw = $entry->values[$fieldId] ?? null;
                if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                    continue;
                }
                $sum += (float) $raw;
                $days++;
            }

            if ($days === 0) {
                continue;
            }

            $candidate = [
                'athlete_id' => $athlete->id,
                'athlete_name' => $athlete->name,
                'value' => $sum,
                'display_value' => number_format((int) round($sum), 0, ',', ' '),
                'days' => $days,
            ];

            if ($best === null || $candidate['value'] > $best['value']) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param  Collection<int, User>  $athletes
     * @param  Collection<int, AthleteReadinessForm>  $formsByAthlete
     * @param  Collection<int, Collection<int, AthleteReadinessEntry>>  $entries
     * @return array{athlete_id: int, athlete_name: string, value: float|int, display_value: string, days: int}|null
     */
    private function winnerForKcalSum(
        Collection $athletes,
        Collection $formsByAthlete,
        Collection $entries,
    ): ?array {
        $best = null;

        foreach ($athletes as $athlete) {
            $fieldId = $this->fieldIdForPreset($formsByAthlete->get($athlete->id), 'kcal');
            if ($fieldId === null) {
                continue;
            }

            $sum = 0.0;
            $days = 0;

            foreach ($entries->get($athlete->id, collect()) as $entry) {
                $parsed = $this->parseKcalValue($entry->values[$fieldId] ?? null);
                if ($parsed === null) {
                    continue;
                }
                $sum += $parsed;
                $days++;
            }

            if ($days === 0) {
                continue;
            }

            $candidate = [
                'athlete_id' => $athlete->id,
                'athlete_name' => $athlete->name,
                'value' => $sum,
                'display_value' => number_format((int) round($sum), 0, ',', ' ').' kcal',
                'days' => $days,
            ];

            if ($best === null || $candidate['value'] > $best['value']) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param  Collection<int, User>  $athletes
     * @param  Collection<int, AthleteReadinessForm>  $formsByAthlete
     * @param  Collection<int, Collection<int, AthleteReadinessEntry>>  $entries
     * @return array{athlete_id: int, athlete_name: string, value: float, display_value: string, days: int}|null
     */
    private function winnerForSleep(
        Collection $athletes,
        Collection $formsByAthlete,
        Collection $entries,
    ): ?array {
        $best = null;

        foreach ($athletes as $athlete) {
            $fieldId = $this->fieldIdForPreset($formsByAthlete->get($athlete->id), 'sommeil');
            if ($fieldId === null) {
                continue;
            }

            $ordinalSum = 0.0;
            $days = 0;
            $bestOption = null;
            $bestOptionCount = 0;
            $optionCounts = [];

            foreach ($entries->get($athlete->id, collect()) as $entry) {
                $raw = (string) ($entry->values[$fieldId] ?? '');
                if ($raw === '' || ! isset(self::SLEEP_ORDINAL[$raw])) {
                    continue;
                }
                $ordinalSum += self::SLEEP_ORDINAL[$raw];
                $days++;
                $optionCounts[$raw] = ($optionCounts[$raw] ?? 0) + 1;
                if ($optionCounts[$raw] > $bestOptionCount
                    || ($optionCounts[$raw] === $bestOptionCount
                        && self::SLEEP_ORDINAL[$raw] > self::SLEEP_ORDINAL[$bestOption ?? 'lt_5h'])) {
                    $bestOption = $raw;
                    $bestOptionCount = $optionCounts[$raw];
                }
            }

            if ($days === 0) {
                continue;
            }

            $average = $ordinalSum / $days;
            $candidate = [
                'athlete_id' => $athlete->id,
                'athlete_name' => $athlete->name,
                'value' => $average,
                'display_value' => self::SLEEP_LABELS[$bestOption] ?? 'bien dormi',
                'days' => $days,
            ];

            if ($best === null || $candidate['value'] > $best['value']) {
                $best = $candidate;
            }
        }

        return $best;
    }

    private function fieldIdForPreset(?AthleteReadinessForm $form, string $presetKey): ?string
    {
        if ($form === null) {
            return null;
        }

        foreach (ReadinessFormSupport::normalizeFields($form->fields ?? []) as $field) {
            if (($field['preset_key'] ?? null) === $presetKey) {
                return (string) $field['id'];
            }
        }

        return null;
    }

    private function parseKcalValue(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        if (! is_string($raw)) {
            return null;
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)/', $raw, $matches) !== 1) {
            return null;
        }

        return (float) str_replace(',', '.', $matches[1]);
    }

    /**
     * @param  array{athlete_id: int, athlete_name: string, value: float|int, display_value: string, days: int}  $winner
     * @return array<string, mixed>
     */
    private function stepsScreen(array $winner): array
    {
        return [
            'id' => 'most_steps',
            'kind' => 'roster_award',
            'award_key' => 'steps',
            'eyebrow' => 'Hall of Fame · Pas',
            'title' => 'Le podomètre a fondu',
            'punchline' => $winner['athlete_name'].' a fait marcher le groupe… littéralement.',
            'athlete_name' => $winner['athlete_name'],
            'value_label' => $winner['display_value'].' pas',
            'footnote' => 'Cumul du mois · '.$winner['days'].' jour'.($winner['days'] > 1 ? 's' : '').' saisi'.($winner['days'] > 1 ? 's' : ''),
        ];
    }

    /**
     * @param  array{athlete_id: int, athlete_name: string, value: float|int, display_value: string, days: int}  $winner
     * @return array<string, mixed>
     */
    private function kcalScreen(array $winner): array
    {
        return [
            'id' => 'most_kcal',
            'kind' => 'roster_award',
            'award_key' => 'kcal',
            'eyebrow' => 'Hall of Fame · Assiette',
            'title' => 'Le roi / la reine de la fourchette',
            'punchline' => $winner['athlete_name'].' a mis le plus de carburant dans le réservoir.',
            'athlete_name' => $winner['athlete_name'],
            'value_label' => $winner['display_value'],
            'footnote' => 'Cumul estimé du mois · '.$winner['days'].' jour'.($winner['days'] > 1 ? 's' : '').' saisi'.($winner['days'] > 1 ? 's' : ''),
        ];
    }

    /**
     * @param  array{athlete_id: int, athlete_name: string, value: float, display_value: string, days: int}  $winner
     * @return array<string, mixed>
     */
    private function sleepScreen(array $winner): array
    {
        return [
            'id' => 'biggest_sleeper',
            'kind' => 'roster_award',
            'award_key' => 'sommeil',
            'eyebrow' => 'Hall of Fame · Oreiller',
            'title' => 'Le plus gros dormeur',
            'punchline' => $winner['athlete_name'].' collectionne les heures de lit comme des médailles.',
            'athlete_name' => $winner['athlete_name'],
            'value_label' => 'Souvent '.$winner['display_value'],
            'footnote' => 'Meilleure moyenne sommeil du mois · '.$winner['days'].' nuit'.($winner['days'] > 1 ? 's' : ''),
        ];
    }
}
