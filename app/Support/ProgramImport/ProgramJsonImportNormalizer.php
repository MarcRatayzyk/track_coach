<?php

namespace App\Support\ProgramImport;

use InvalidArgumentException;

/**
 * Normalise un JSON coach (imbriqué weeks[] ou plat rows[]) vers les rows du draft builder.
 */
class ProgramJsonImportNormalizer
{
    public const FORMAT = 'track_coach_program_v1';

    /**
     * @return list<array<string, mixed>>
     */
    public function normalize(mixed $payload): array
    {
        if (is_string($payload)) {
            $payload = $this->decodeJsonString($payload);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Le JSON doit être un objet (weeks[] ou rows[]).');
        }

        // Racine = liste de rows
        if (array_is_list($payload) && $payload !== []) {
            return $this->normalizeFlatRows($payload);
        }

        if (isset($payload['rows']) && is_array($payload['rows'])) {
            return $this->normalizeFlatRows($payload['rows']);
        }

        if (isset($payload['weeks']) && is_array($payload['weeks'])) {
            return $this->normalizeNestedWeeks($payload['weeks']);
        }

        throw new InvalidArgumentException(
            'Format non reconnu. Attendu : { "weeks": [...] } ou { "rows": [...] } (format '.self::FORMAT.').',
        );
    }

    /**
     * Gabarit imbriqué prérempli pour N semaines × daysPerWeek séances.
     *
     * @return array<string, mixed>
     */
    public function skeleton(int $weekCount, int $daysPerWeek = 4): array
    {
        $weekCount = max(1, min(16, $weekCount));
        $daysPerWeek = max(1, min(7, $daysPerWeek));

        $weeks = [];
        for ($w = 1; $w <= $weekCount; $w++) {
            $sessions = [];
            for ($d = 1; $d <= $daysPerWeek; $d++) {
                $sessions[] = [
                    'day' => $d,
                    'session_label' => 'SÉANCE '.((($w - 1) * $daysPerWeek) + $d),
                    'focus' => null,
                    'is_rest' => false,
                    'exercises' => [
                        [
                            'name' => '',
                            'sets' => '',
                            'reps' => '',
                            'charge' => null,
                            'notes' => null,
                        ],
                    ],
                ];
            }
            $weeks[] = [
                'week' => $w,
                'sessions' => $sessions,
            ];
        }

        return [
            'format' => self::FORMAT,
            'weeks' => $weeks,
        ];
    }

    /**
     * Prompt unique à coller dans une IA externe (format JSON inclus).
     * S’adapte à n’importe quel tableau coach (jours, colonnes, layout variables).
     */
    public function externalAiPrompt(int $weekCount = 0): string
    {
        $weeksHint = $weekCount > 0
            ? "Le bloc Track Coach cible environ {$weekCount} semaine(s) — adapte-toi au document s’il en a plus ou moins."
            : 'Déduis toi-même le nombre de semaines et de séances depuis le document.';

        return <<<PROMPT
Tu extrais un programme de musculation / powerlifting depuis le fichier, Excel, PDF ou photo fourni.

{$weeksHint}

OBJECTIF
Remplir UNIQUEMENT le JSON track_coach_program_v1 ci-dessous.
Adapter la structure au document réel — ne force PAS un modèle fixe (pas forcément 4 jours/semaine, pas forcément les mêmes colonnes que l’exemple).

LE DOCUMENT PEUT VARIER
- 2, 3, 4, 5, 6… jours d’entraînement par semaine
- Grille semaines×séances, une feuille = une semaine, listes verticales, plusieurs tableaux
- Titres : Séance 1, Session A, Upper, Lower, Jour 1, Bench day, etc.
- Colonnes variables : Exercice, Séries, Reps, Charge, %, RPE, RIR, Tempo, Repos, Notes, Instructions, Vidéo…
- Cellules fusionnées, barres REPOS entre colonnes, cases REPOS entières

RÈGLES D’EXTRACTION
1. Une ligne d’exercice visible = un objet dans exercises[]
2. Cellule fusionnée (même exo sur plusieurs lignes séries/reps) : répète le name sur chaque ligne
3. Textes EXACTS : "167,5kg", "70%RM", "RPE8", "-10 %", "2-3", "10-12" — ne calcule pas, ne convertis pas
4. Barres / colonnes "REPOS" entre séances = séparateurs, PAS des séances
5. Séance entière REPOS : is_rest=true et exercises=[]
6. day = index de séance dans la semaine (1, 2, 3…) selon l’ordre du document, même s’il n’y a que 3 jours
7. Colonnes hors name/sets/reps/charge → mets-les dans cells (clés = libellés exacts du tableau) OU dans notes
8. Si une colonne s’appelle autrement (Poids, Weight, Kg, Séries, Series…) mappe vers sets/reps/charge quand c’est clair, sinon cells
9. N’invente rien. Si illisible : null pour la valeur concernée
10. Remplis detected avec ce que tu observes (week_count, days_per_week peut varier d’une semaine à l’autre → utilise days_per_week_typical)

FORMAT JSON À REMPLIR (exemple illustratif — adapte le nombre de weeks/sessions/exercises et les colonnes) :

{
  "format": "track_coach_program_v1",
  "detected": {
    "week_count": 5,
    "days_per_week_typical": 4,
    "layout": "description courte de la disposition réelle",
    "column_headers": ["Exercice", "Séries", "Reps", "Charge"]
  },
  "weeks": [
    {
      "week": 1,
      "sessions": [
        {
          "day": 1,
          "session_label": "titre exact de la séance",
          "focus": "sous-titre / thème si présent, sinon null",
          "is_rest": false,
          "exercises": [
            {
              "name": "Squat",
              "sets": "1",
              "reps": "8",
              "charge": "167,5kg",
              "notes": null,
              "cells": {
                "Tempo": "3010",
                "Repos": "3 min"
              }
            },
            {
              "name": "Squat",
              "sets": "3",
              "reps": "8",
              "charge": "152,5kg",
              "notes": null,
              "cells": {}
            }
          ]
        },
        {
          "day": 2,
          "session_label": "autre séance",
          "focus": null,
          "is_rest": false,
          "exercises": []
        }
      ]
    }
  ]
}

Champs exercises (souples) :
- requis : name, sets, reps (chaînes)
- recommandés : charge (chaîne), notes
- optionnels : cells (objet clé→valeur pour toute autre colonne du tableau), section, parent_name, main_lift, rpe, load_percent
- Alias acceptés côté import : exercise/exercice, series/séries, repetitions, weight/poids/kg/load

Réponds UNIQUEMENT avec le JSON valide, sans markdown, sans commentaire, sans texte autour.
PROMPT;
    }

    /**
     * Même format, limité à une semaine (fallback si réponse tronquée).
     */
    public function weekFocusPrompt(int $week): string
    {
        $week = max(1, $week);

        return <<<PROMPT
Extrais UNIQUEMENT la Semaine {$week} du programme (fichier / photo / PDF fourni).

Remplis le JSON track_coach_program_v1 avec un seul bloc weeks[] pour week={$week}.
Adapte le nombre de séances (2–6…) et les colonnes au document.
Textes exacts (charges, plages, RPE). Cellules fusionnées : répète le name.
REPOS entre colonnes = séparateur. Séance REPOS : is_rest=true, exercises=[].
Colonnes extras → cells.

{
  "format": "track_coach_program_v1",
  "detected": {
    "week_count": null,
    "days_per_week_typical": null,
    "layout": null,
    "column_headers": []
  },
  "weeks": [
    {
      "week": {$week},
      "sessions": [
        {
          "day": 1,
          "session_label": "...",
          "focus": null,
          "is_rest": false,
          "exercises": [
            { "name": "...", "sets": "...", "reps": "...", "charge": "...", "notes": null, "cells": {} }
          ]
        }
      ]
    }
  ]
}

Si cette semaine n’existe pas dans le document, renvoie weeks: [] .
Réponds UNIQUEMENT avec le JSON valide.
PROMPT;
    }

    /**
     * @param  list<mixed>  $weeks
     * @return list<array<string, mixed>>
     */
    private function normalizeNestedWeeks(array $weeks): array
    {
        $rows = [];

        foreach ($weeks as $weekBlock) {
            if (! is_array($weekBlock)) {
                continue;
            }

            $week = $this->intOrNull($weekBlock['week'] ?? $weekBlock['week_number'] ?? null);
            $sessions = $weekBlock['sessions'] ?? $weekBlock['days'] ?? [];
            if (! is_array($sessions)) {
                continue;
            }

            foreach ($sessions as $sessionIndex => $session) {
                if (! is_array($session)) {
                    continue;
                }

                if (! empty($session['is_rest'])) {
                    continue;
                }

                $day = $this->intOrNull($session['day'] ?? $session['weekday'] ?? null);
                if ($day === null) {
                    $day = $sessionIndex + 1;
                }
                $sessionLabel = $this->stringOrNull($session['session_label'] ?? $session['label'] ?? $session['title'] ?? null);
                $focus = $this->stringOrNull($session['focus'] ?? null);
                $exercises = $session['exercises'] ?? $session['items'] ?? [];
                if (! is_array($exercises)) {
                    continue;
                }

                foreach ($exercises as $exercise) {
                    if (! is_array($exercise)) {
                        continue;
                    }

                    $row = $this->mapExerciseToRow($exercise, $week, $day, $sessionLabel, $focus);
                    if ($row !== null) {
                        $rows[] = $row;
                    }
                }
            }
        }

        if ($rows === []) {
            throw new InvalidArgumentException(
                'Aucune exercice exploitable dans weeks[]. Vérifie name/sets/reps.',
            );
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $rawRows
     * @return list<array<string, mixed>>
     */
    private function normalizeFlatRows(array $rawRows): array
    {
        $rows = [];

        foreach ($rawRows as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            if (! empty($raw['is_rest'])) {
                continue;
            }

            $row = $this->mapExerciseToRow(
                $raw,
                $this->intOrNull($raw['week'] ?? $raw['week_number'] ?? null),
                $this->intOrNull($raw['day'] ?? $raw['weekday'] ?? null),
                $this->stringOrNull($raw['session_label'] ?? $raw['label'] ?? null),
                $this->stringOrNull($raw['focus'] ?? null),
            );

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        if ($rows === []) {
            throw new InvalidArgumentException(
                'Aucune exercice exploitable dans rows[]. Vérifie week, day, exercise/name, sets, reps.',
            );
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $exercise
     * @return array<string, mixed>|null
     */
    private function mapExerciseToRow(
        array $exercise,
        ?int $week,
        ?int $day,
        ?string $sessionLabel,
        ?string $focus,
    ): ?array {
        // Fusionne cells/extra dans l’exercice pour un mapping uniforme.
        $exercise = $this->flattenExerciseFields($exercise);

        $name = trim((string) (
            $this->firstPresent($exercise, [
                'name', 'exercise', 'exercice', 'variant_name', 'mouvement', 'movement', 'exo',
            ]) ?? ''
        ));

        if ($name === '' || preg_match('/^repos$/iu', $name)) {
            return null;
        }

        $sets = $this->scalarToString($this->firstPresent($exercise, [
            'sets', 'sets_raw', 'series', 'séries', 'series_count', 'nb_series', 'nb_séries',
        ]));
        $reps = $this->scalarToString($this->firstPresent($exercise, [
            'reps', 'reps_raw', 'rep', 'repetitions', 'répétitions', 'repetition', 'répétition',
        ]));
        $charge = $this->scalarToString($this->firstPresent($exercise, [
            'charge', 'charge_raw', 'load', 'weight', 'poids', 'kg', 'intensité', 'intensity',
        ]));

        $notes = $this->stringOrNull($exercise['notes'] ?? $exercise['comment'] ?? $exercise['commentaire'] ?? null);
        $extraNotes = [];

        $known = [
            'name', 'exercise', 'exercice', 'variant_name', 'parent_name', 'pass_lift',
            'mouvement', 'movement', 'exo',
            'sets', 'sets_raw', 'series', 'séries', 'series_count', 'nb_series', 'nb_séries',
            'reps', 'reps_raw', 'rep', 'repetitions', 'répétitions', 'repetition', 'répétition',
            'charge', 'charge_raw', 'load', 'weight', 'poids', 'kg', 'intensité', 'intensity',
            'load_percent', 'rpe', 'rir',
            'notes', 'comment', 'commentaire', 'section', 'main_lift', 'lift', 'category',
            'week', 'week_number', 'day', 'weekday', 'session_label', 'label',
            'focus', 'is_rest', 'rest_seconds', 'cells', 'extra', 'columns',
            'tempo', 'repos', 'rest', 'récup', 'recup', 'instructions', 'video', 'vidéo',
        ];

        // Colonnes métier utiles hors charge → notes si non vides.
        foreach (['tempo', 'repos', 'rest', 'récup', 'recup', 'instructions', 'video', 'vidéo', 'rir'] as $softKey) {
            if (! array_key_exists($softKey, $exercise)) {
                continue;
            }
            $val = $this->scalarToString($exercise[$softKey]);
            if ($val !== '') {
                $extraNotes[] = $softKey.': '.$val;
            }
        }

        foreach ($exercise as $key => $value) {
            if (! is_string($key) || in_array(mb_strtolower($key), array_map('mb_strtolower', $known), true)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (is_scalar($value)) {
                $extraNotes[] = $key.': '.(string) $value;
            }
        }
        if ($extraNotes !== []) {
            $joined = implode(' · ', $extraNotes);
            $notes = $notes !== null && $notes !== '' ? $notes.' · '.$joined : $joined;
        }

        $weekVal = $week ?? $this->intOrNull($exercise['week'] ?? $exercise['week_number'] ?? null);
        $dayVal = $day ?? $this->intOrNull($exercise['day'] ?? $exercise['weekday'] ?? null);

        $rpe = $this->firstPresent($exercise, ['rpe', 'RPE']);
        $loadPercent = $this->firstPresent($exercise, ['load_percent', 'percent', '%', 'pct']);

        return [
            ProgramCsvColumns::WEEK => $weekVal ?? '',
            ProgramCsvColumns::DAY => $dayVal ?? '',
            ProgramCsvColumns::SECTION => $exercise['section'] ?? 'accessory',
            ProgramCsvColumns::EXERCISE => $name,
            ProgramCsvColumns::SETS => $sets,
            ProgramCsvColumns::REPS => $reps,
            'sets_raw' => $sets,
            'reps_raw' => $reps,
            ProgramCsvColumns::LOAD => $exercise['load'] ?? '',
            ProgramCsvColumns::LOAD_PERCENT => $loadPercent ?? '',
            ProgramCsvColumns::RPE => $rpe ?? '',
            ProgramCsvColumns::REST_SECONDS => $exercise['rest_seconds'] ?? '',
            ProgramCsvColumns::MAIN_LIFT => $exercise['main_lift'] ?? $exercise['lift'] ?? '',
            ProgramCsvColumns::SESSION_LABEL => $sessionLabel
                ?? $this->stringOrNull($exercise['session_label'] ?? null)
                ?? '',
            ProgramCsvColumns::NOTES => $notes ?? '',
            'parent_name' => $exercise['parent_name'] ?? $exercise['pass_lift'] ?? '',
            'variant_name' => $name,
            'category' => $exercise['category'] ?? '',
            'charge_raw' => $charge,
        ];
    }

    /**
     * Fusionne cells / extra / colonnes FR dans un seul tableau clé→valeur.
     *
     * @param  array<string, mixed>  $exercise
     * @return array<string, mixed>
     */
    private function flattenExerciseFields(array $exercise): array
    {
        foreach (['cells', 'extra', 'columns'] as $bag) {
            if (! isset($exercise[$bag]) || ! is_array($exercise[$bag])) {
                continue;
            }
            foreach ($exercise[$bag] as $key => $value) {
                if (! is_string($key) && ! is_int($key)) {
                    continue;
                }
                $keyStr = (string) $key;
                if ($keyStr === '' || array_key_exists($keyStr, $exercise)) {
                    continue;
                }
                $exercise[$keyStr] = $value;

                // Map libellés FR/EN courants vers champs canoniques.
                $normalized = $this->normalizeColumnKey($keyStr);
                if ($normalized !== null && ! array_key_exists($normalized, $exercise)) {
                    $exercise[$normalized] = $value;
                }
            }
            unset($exercise[$bag]);
        }

        // Aussi normaliser les clés top-level FR.
        foreach (array_keys($exercise) as $key) {
            if (! is_string($key)) {
                continue;
            }
            $normalized = $this->normalizeColumnKey($key);
            if ($normalized !== null && $normalized !== $key && ! array_key_exists($normalized, $exercise)) {
                $exercise[$normalized] = $exercise[$key];
            }
        }

        return $exercise;
    }

    private function normalizeColumnKey(string $key): ?string
    {
        $k = mb_strtolower(trim($key));
        $k = strtr($k, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'â' => 'a',
            'ù' => 'u', 'û' => 'u', 'ô' => 'o', 'î' => 'i', 'ç' => 'c',
        ]);
        $k = preg_replace('/[^a-z0-9%]+/u', '_', $k) ?? $k;
        $k = trim($k, '_');

        return match ($k) {
            'exercice', 'exercise', 'exo', 'mouvement', 'movement', 'name', 'nom' => 'name',
            'series', 'serie', 'sets', 'nb_series', 'nb_serie' => 'sets',
            'reps', 'rep', 'repetitions', 'repetition' => 'reps',
            'charge', 'poids', 'weight', 'kg', 'load', 'intensite', 'intensity' => 'charge',
            'rpe' => 'rpe',
            'rir' => 'rir',
            'percent', 'pct', '%', 'pourcent', 'pourcentage', 'load_percent' => 'load_percent',
            'tempo' => 'tempo',
            'repos', 'rest', 'recup', 'recuperation', 'rest_seconds' => 'rest',
            'notes', 'note', 'commentaire', 'comment', 'comments', 'instructions' => 'notes',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private function firstPresent(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($value === null || $value === '') {
                continue;
            }

            return $value;
        }

        // Recherche insensible à la casse / accents via normalizeColumnKey.
        foreach ($data as $key => $value) {
            if (! is_string($key) || $value === null || $value === '') {
                continue;
            }
            $normalized = $this->normalizeColumnKey($key);
            if ($normalized !== null && in_array($normalized, $keys, true)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonString(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new InvalidArgumentException('Colle un JSON non vide.');
        }

        // Enlever fences markdown éventuelles.
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $raw, $m)) {
            $raw = trim($m[1]);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('JSON invalide : '.json_last_error_msg().'.');
        }

        return $decoded;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    private function scalarToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return trim((string) $value);
    }
}
