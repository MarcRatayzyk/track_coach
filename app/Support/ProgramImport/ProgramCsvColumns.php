<?php

namespace App\Support\ProgramImport;

class ProgramCsvColumns
{
    public const WEEK = 'week';

    public const DAY = 'day';

    public const SECTION = 'section';

    public const EXERCISE = 'exercise';

    public const SETS = 'sets';

    public const REPS = 'reps';

    public const LOAD = 'load';

    public const LOAD_PERCENT = 'load_percent';

    public const RPE = 'rpe';

    public const REST_SECONDS = 'rest_seconds';

    public const MAIN_LIFT = 'main_lift';

    public const SESSION_LABEL = 'session_label';

    public const NOTES = 'notes';

    /**
     * Official Track Coach CSV headers (P1).
     *
     * @return list<string>
     */
    public static function official(): array
    {
        return [
            self::WEEK,
            self::DAY,
            self::SECTION,
            self::EXERCISE,
            self::SETS,
            self::REPS,
            self::LOAD,
            self::LOAD_PERCENT,
            self::RPE,
            self::REST_SECONDS,
            self::MAIN_LIFT,
            self::SESSION_LABEL,
            self::NOTES,
        ];
    }

    /**
     * Target fields the coach can map flexible CSV columns onto (P2).
     *
     * @return list<array{key: string, label: string, required: bool}>
     */
    public static function mappableFields(): array
    {
        return [
            ['key' => self::WEEK, 'label' => 'Semaine', 'required' => true],
            ['key' => self::DAY, 'label' => 'Jour (1–7)', 'required' => true],
            ['key' => self::EXERCISE, 'label' => 'Exercice', 'required' => true],
            ['key' => self::SETS, 'label' => 'Séries', 'required' => true],
            ['key' => self::REPS, 'label' => 'Répétitions', 'required' => true],
            ['key' => self::SECTION, 'label' => 'Section (topset/backoff/accessory)', 'required' => false],
            ['key' => self::LOAD, 'label' => 'Charge (kg)', 'required' => false],
            ['key' => self::LOAD_PERCENT, 'label' => '% 1RM', 'required' => false],
            ['key' => self::RPE, 'label' => 'RPE', 'required' => false],
            ['key' => self::REST_SECONDS, 'label' => 'Repos (s)', 'required' => false],
            ['key' => self::MAIN_LIFT, 'label' => 'Mouvement principal', 'required' => false],
            ['key' => self::SESSION_LABEL, 'label' => 'Titre de séance', 'required' => false],
            ['key' => self::NOTES, 'label' => 'Notes', 'required' => false],
        ];
    }

    /**
     * Header aliases used to auto-suggest mappings for flexible CSV.
     *
     * @return array<string, list<string>>
     */
    public static function aliases(): array
    {
        return [
            self::WEEK => ['week', 'semaine', 'week_number', 'weeknumber', 'sem'],
            self::DAY => ['day', 'jour', 'weekday', 'day_number', 'daynumber', 'dayofweek'],
            self::SECTION => ['section', 'type', 'block', 'slot'],
            self::EXERCISE => ['exercise', 'exercice', 'mouvement', 'movement', 'name', 'exo', 'exercise_name'],
            self::SETS => ['sets', 'series', 'séries', 'series_count', 'nb_series'],
            self::REPS => ['reps', 'rep', 'repetitions', 'répétitions', 'repetition'],
            self::LOAD => ['load', 'charge', 'kg', 'weight', 'poids'],
            self::LOAD_PERCENT => ['load_percent', 'percent', 'pourcent', '%', 'pct', 'intensity', 'intensite'],
            self::RPE => ['rpe', 'rir'],
            self::REST_SECONDS => ['rest_seconds', 'rest', 'repos', 'rest_sec', 'récup', 'recup'],
            self::MAIN_LIFT => ['main_lift', 'lift', 'mouvement_principal', 'primary_lift'],
            self::SESSION_LABEL => ['session_label', 'label', 'titre', 'session', 'seance', 'séance'],
            self::NOTES => ['notes', 'note', 'comment', 'commentaire', 'comments'],
        ];
    }

    public static function templateCsv(): string
    {
        $headers = self::official();
        $rows = [
            $headers,
            ['1', '1', 'topset', 'Squat', '1', '3', '', '85', '8', '180', 'squat', 'Jour 1', ''],
            ['1', '1', 'backoff', 'Squat', '3', '5', '', '75', '7', '180', 'squat', 'Jour 1', ''],
            ['1', '1', 'accessory', 'Leg Curl', '3', '10', '', '', '8', '90', 'squat', 'Jour 1', ''],
            ['1', '2', 'topset', 'Bench Press', '1', '3', '', '82.5', '8', '180', 'bench', 'Jour 2', ''],
            ['1', '2', 'backoff', 'Bench Press', '3', '6', '', '72.5', '7', '150', 'bench', 'Jour 2', ''],
            ['1', '2', 'accessory', 'Row', '3', '8', '', '', '8', '90', 'bench', 'Jour 2', ''],
        ];

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(
                static fn (string $value): string => self::escapeCsvField($value),
                $row,
            ));
        }

        return implode("\n", $lines)."\n";
    }

    private static function escapeCsvField(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
