<?php

namespace App\Support\ProgramImport;

/**
 * Parse free-form charge / sets / reps strings from coach spreadsheets.
 */
class ProgramChargeParser
{
    /**
     * @return array{load: float|null, load_percent: float|null, rpe: float|null, notes: string|null}
     */
    public function parse(mixed $raw): array
    {
        if ($raw === null) {
            return ['load' => null, 'load_percent' => null, 'rpe' => null, 'notes' => null];
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return ['load' => null, 'load_percent' => null, 'rpe' => null, 'notes' => null];
        }

        $text = str_replace(["\u{00A0}", '−', '–', '—'], [' ', '-', '-', '-'], $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        $notes = null;
        $upper = mb_strtoupper($text);

        if (str_contains($upper, 'TEST') || (str_contains($upper, '1RM') && str_contains($upper, 'OU'))) {
            $notes = $text;
        }

        $rpe = null;
        if (preg_match('/RPE\s*([0-9]+(?:[.,][0-9])?)/iu', $text, $m)) {
            $rpe = (float) str_replace(',', '.', $m[1]);
        }

        $loadPercent = null;
        if (preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*%\s*(?:RM)?/iu', $text, $m)) {
            $loadPercent = (float) str_replace(',', '.', $m[1]);
        } elseif (preg_match('/^\s*-+\s*([0-9]+(?:[.,][0-9]+)?)\s*%\s*$/u', $text)) {
            $notes = $notes ?? $text;
        }

        $load = null;
        if ($loadPercent === null && preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*kg/iu', $text, $m)) {
            $load = (float) str_replace(',', '.', $m[1]);
        } elseif ($loadPercent === null && $rpe === null && preg_match('/^([0-9]+(?:[.,][0-9]+)?)$/u', $text, $m)) {
            $load = (float) str_replace(',', '.', $m[1]);
        }

        if (preg_match('/\bPDC\b/iu', $text)) {
            $notes = $notes ?? 'PDC';
        }

        return [
            'load' => $load,
            'load_percent' => $loadPercent,
            'rpe' => $rpe,
            'notes' => $notes,
        ];
    }

    /**
     * Single value = exact. Range "10-12" → higher bound (target).
     */
    public function parseIntRange(mixed $raw, int $default = 1): int
    {
        if ($raw === null || $raw === '') {
            return $default;
        }

        if (is_int($raw) || is_float($raw)) {
            return max(1, (int) $raw);
        }

        $text = trim((string) $raw);
        $text = str_replace(['−', '–', '—'], '-', $text);

        if (preg_match('/^\s*(\d+)\s*$/u', $text, $m)) {
            return max(1, (int) $m[1]);
        }

        if (preg_match('/(\d+)\s*-\s*(\d+)/u', $text, $m)) {
            return max(1, max((int) $m[1], (int) $m[2]));
        }

        if (preg_match('/(\d+)/u', $text, $m)) {
            return max(1, (int) $m[1]);
        }

        return $default;
    }

    public function parseRestSeconds(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            $n = (float) $raw;
            if ($n > 0 && $n <= 30) {
                return (int) round($n * 60);
            }

            return (int) round($n);
        }

        $text = mb_strtolower(trim((string) $raw));
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*min/u', $text, $m)) {
            return (int) round(((float) str_replace(',', '.', $m[1])) * 60);
        }
        if (preg_match('/(\d+)\s*s/u', $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\d+)/u', $text, $m)) {
            $n = (int) $m[1];

            return $n <= 30 ? $n * 60 : $n;
        }

        return null;
    }
}
