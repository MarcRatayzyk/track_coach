<?php

namespace App\Support\ProgramImport;

/**
 * Pipeline souple :
 * 1) détail lisible (comme une relecture humaine)
 * 2) mapping JSON métier (champs connus, peu de contraintes)
 */
final class ProgramExtractionPrompts
{
    public static function stage1ReadableSystem(): string
    {
        return <<<'PROMPT'
Tu lis des programmes de musculation / powerlifting (Excel, PDF, photo, scan).

Ta seule mission : ressaisir le programme en détail LISIBLE et FIDÈLE.
Tu n'interprètes pas, tu ne calcules pas, tu ne reformates pas les chiffres.

Écris comme si tu dictais le tableau à quelqu'un :
- toutes les séances
- tous les exercices
- toutes les lignes (topset et backoff = lignes séparées)
- charges EXACTES : 167,5kg, 70%RM, RPE8, -10 %, 10-12, etc.
- cellules fusionnées : répète le nom d'exercice sur chaque ligne

Les barres "REPOS" entre colonnes sont des séparateurs, pas des séances.
Une case séance entière "REPOS" = note-le comme repos (sans exercices).

Réponds en JSON avec surtout du texte markdown lisible. Peu de champs techniques.
PROMPT;
    }

    /**
     * Étape 1 libre : détail humain, pas de schéma métier.
     */
    public static function stage1ReadableUser(): string
    {
        return <<<'PROMPT'
Lis le document et ressors le DÉTAIL DES SÉANCES en markdown clair.

Exemple de style attendu :

### SÉANCE 1 — SQUAT BENCH DEADLIFT VOLUME
| Exercice | Séries | Reps | Charge |
|---|---|---|---|
| Squat | 1 | 8 | 167,5kg |
| Squat | 3 | 8 | 152,5kg |
| DC | 1 | 8 | 110kg |
…

### SÉANCE 2 — …
…

Si plusieurs semaines : utilise des titres `## Semaine 1`, `## Semaine 2`, etc.

Retourne UNIQUEMENT ce JSON simple :
{
  "week_count": 5,
  "session_count": 20,
  "layout": "grille semaines × séances (courte description)",
  "readable_detail": "…tout le markdown ici…"
}

Pas de champs week/day/section/sets_raw ici.
Pas de JSON métier.
Si une valeur est illisible, écris `[illisible]` dans la cellule.
N'invente rien. Ne résume pas. Ne saute aucune ligne.
PROMPT;
    }

    public static function stage1RetryUser(string $previousJson, string $reason): string
    {
        return <<<PROMPT
Relis le document : le détail précédent est incomplet.

Raison : {$reason}

JSON précédent :
```json
{$previousJson}
```

Complète / corrige `readable_detail` (markdown) pour coller au document.
Même format JSON simple : week_count, session_count, layout, readable_detail.
PROMPT;
    }

    public static function stage1WeekFocusUser(array $weeks): string
    {
        $list = implode(', ', $weeks);

        return <<<PROMPT
Lis le document et ressors le DÉTAIL LISIBLE UNIQUEMENT pour les Semaines {$list}.

Même style markdown (titres de séances + tableaux Exercice | Séries | Reps | Charge).
Cellules fusionnées : répète le nom d'exercice. Charges exactes. Pas d'invention.

JSON simple :
{
  "week_count": null,
  "session_count": null,
  "layout": null,
  "focus_weeks": [{$list}],
  "readable_detail": "…markdown des semaines {$list}…"
}
PROMPT;
    }

    public static function stage2MapSystem(): string
    {
        return <<<'PROMPT'
Tu convertis un détail de programme DÉJÀ LU (markdown lisible) en JSON métier Track Coach.

Le texte source est la vérité : tu ne relis pas une image, tu ranges les infos dans les bons champs.
Ne calcule pas, ne convertis pas les unités, ne modifie pas les chiffres.
JSON uniquement.
PROMPT;
    }

    /**
     * Étape 2 : détail lisible → rows (contraintes légères).
     */
    public static function stage2MapUser(string $readableDetail, ?string $metaJson = null): string
    {
        $metaBlock = $metaJson
            ? "Meta (indicatif) :\n```json\n{$metaJson}\n```\n\n"
            : '';

        return <<<PROMPT
{$metaBlock}Voici le détail lisible du programme :
```markdown
{$readableDetail}
```

Range chaque ligne d'exercice dans ce JSON :
{
  "rows": [
    {
      "week": 1,
      "day": 1,
      "session_label": "SÉANCE 1",
      "variant_name": "Squat",
      "parent_name": "Squat",
      "sets_raw": "1",
      "reps_raw": "8",
      "charge_raw": "167,5kg",
      "section": "topset",
      "main_lift": "squat",
      "notes": null
    }
  ]
}

Où mettre quoi (simple) :
- week / day : d'après « Semaine N » et l'ordre des séances (1,2,3…)
- session_label : titre de séance
- variant_name : nom d'exercice exact
- sets_raw / reps_raw / charge_raw : texte exact des colonnes (garde 2-3, 10-12, 167,5kg, RPE6, -10 %, 70%RM…)
- section : 1ère ligne d'un mouvement principal = topset, lignes suivantes du même exo = backoff, sinon accessory
- main_lift : squat|bench|deadlift si évident (DC→bench, SDT→deadlift), sinon null
- charge vide → charge_raw null ou ""
- séance REPOS → aucune row

Une ligne markdown = une row. N'en saute aucune.
Pas besoin de load / load_percent / rpe numériques (laisse null) : charge_raw suffit.
PROMPT;
    }

    public static function stage2RetryUser(string $readableDetail, string $previousRowsJson, string $reason): string
    {
        return <<<PROMPT
Le mapping précédent a un problème : {$reason}

Détail lisible (source de vérité) :
```markdown
{$readableDetail}
```

Mapping précédent :
```json
{$previousRowsJson}
```

Re-mappe simplement : 1 ligne markdown = 1 row, textes exacts dans sets_raw / reps_raw / charge_raw.
Retourne {"rows":[...]} uniquement.
PROMPT;
    }
}
