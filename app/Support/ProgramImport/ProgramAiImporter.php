<?php

namespace App\Support\ProgramImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProgramAiImporter
{
    public function __construct(
        private readonly ProgramCsvImporter $spreadsheetImporter = new ProgramCsvImporter,
    ) {}

    public function isConfigured(): bool
    {
        $key = config('program_import.openai_api_key');

        return is_string($key) && trim($key) !== '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function extractRows(UploadedFile $file, int $coachId): array
    {
        $this->raiseExecutionLimits();

        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'file' => 'L’import IA n’est pas configuré. Ajoutez PROGRAM_IMPORT_OPENAI_API_KEY dans .env.',
            ]);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $payload = $this->prepareSourcePayload($file, $coachId, $ext);

        // Spreadsheet text is clearer than screenshots: fewer API calls → less 429.
        if (($payload['kind'] ?? '') === 'text') {
            return $this->extractRowsFromSpreadsheetText($payload);
        }

        return $this->extractRowsMultiPass($payload);
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, ext: string}  $payload
     * @return list<array<string, mixed>>
     */
    private function extractRowsFromSpreadsheetText(array $payload): array
    {
        $allRows = [];

        try {
            // One-shot extract (CSV/XLSX text is usually complete enough).
            $decoded = $this->callModel(
                $this->buildFullExtractContent($payload),
                $this->extractSystemPrompt(),
            );
            $allRows = $this->mapRawRows($decoded['rows'] ?? []);
        } catch (ProgramAiResponseTruncated) {
            // Trop long en un seul coup → découpe semaine par semaine.
            $allRows = $this->extractRowsMultiPass($payload, verify: false);
        }

        // If too thin, fall back to multi-pass without verify spam.
        $sessionCount = collect($allRows)
            ->map(fn (array $r) => ((int) $r[ProgramCsvColumns::WEEK]).'-'.((int) $r[ProgramCsvColumns::DAY]))
            ->unique()
            ->count();

        if ($sessionCount < 3) {
            $allRows = $this->extractRowsMultiPass($payload, verify: false);
        } elseif ((bool) config('program_import.verify_numbers', true)) {
            // Single verify pass for the whole spreadsheet (not per-week).
            $weeks = collect($allRows)
                ->map(fn (array $r) => (int) ($r[ProgramCsvColumns::WEEK] ?? 0))
                ->filter(fn (int $w) => $w > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();
            $allRows = $this->verifyBatchNumbers($payload, $weeks, $allRows);
        }

        if ($allRows === []) {
            throw ValidationException::withMessages([
                'file' => 'Aucun exercice détecté dans le fichier.',
            ]);
        }

        return $allRows;
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, ext: string}  $payload
     * @return list<array<string, mixed>>
     */
    private function extractRowsMultiPass(array $payload, bool $verify = true): array
    {
        // Pass 1 — inventory of all sessions (forces the model to see the full grid).
        $inventory = $this->callModel(
            $this->buildInventoryContent($payload),
            $this->inventorySystemPrompt(),
        );

        $sessions = $this->normalizeSessionInventory($inventory);
        if ($sessions === []) {
            $decoded = $this->callModel(
                $this->buildFullExtractContent($payload),
                $this->extractSystemPrompt(),
            );

            return $this->mapRawRows($decoded['rows'] ?? []);
        }

        $weeks = collect($sessions)->pluck('week')->unique()->sort()->values()->all();
        $isText = ($payload['kind'] ?? '') === 'text';
        // Text multi-pass = découpe semaine (évite truncation one-shot). Images/PDF : petits lots.
        $batchSize = max(1, (int) config('program_import.weeks_per_batch', 2));
        if ($isText) {
            $batchSize = min($batchSize, 2);
        }

        $allRows = [];

        foreach (array_chunk($weeks, $batchSize) as $weekBatch) {
            $batchSessions = array_values(array_filter(
                $sessions,
                static fn (array $s): bool => in_array((int) $s['week'], array_map('intval', $weekBatch), true),
            ));

            try {
                $decoded = $this->callModel(
                    $this->buildWeeksExtractContent($payload, array_map('intval', $weekBatch), $batchSessions),
                    $this->extractSystemPrompt(),
                );
            } catch (ProgramAiResponseTruncated) {
                // Lot trop gros → une semaine à la fois.
                $decoded = ['rows' => []];
                foreach (array_map('intval', $weekBatch) as $singleWeek) {
                    $singleSessions = array_values(array_filter(
                        $batchSessions,
                        static fn (array $s): bool => (int) $s['week'] === $singleWeek,
                    ));
                    $part = $this->callModel(
                        $this->buildWeeksExtractContent($payload, [$singleWeek], $singleSessions),
                        $this->extractSystemPrompt(),
                    );
                    foreach ($this->mapRawRows($part['rows'] ?? []) as $row) {
                        $allRows[] = $row;
                    }
                }
                continue;
            }

            foreach ($this->mapRawRows($decoded['rows'] ?? []) as $row) {
                $allRows[] = $row;
            }
        }

        $expectedSessions = count(array_filter($sessions, static fn (array $s): bool => ! ($s['is_rest'] ?? false)));
        $gotSessions = collect($allRows)
            ->map(fn (array $r) => ((int) $r[ProgramCsvColumns::WEEK]).'-'.((int) $r[ProgramCsvColumns::DAY]))
            ->unique()
            ->count();

        if ($expectedSessions >= 4 && $gotSessions < (int) floor($expectedSessions * 0.4)) {
            $decoded = $this->callModel(
                $this->buildFullExtractContent($payload, $sessions),
                $this->extractSystemPrompt(),
            );
            $allRows = $this->mergeRows($allRows, $this->mapRawRows($decoded['rows'] ?? []));
        }

        if ($allRows === []) {
            throw ValidationException::withMessages([
                'file' => 'Aucun exercice détecté dans le fichier.',
            ]);
        }

        $shouldVerify = $verify && (bool) config('program_import.verify_numbers', true);
        // Skip heavy verify on text unless explicitly wanted — reduces 429.
        if ($shouldVerify && ! $isText) {
            $verified = [];
            $verifyBatch = max(1, (int) config('program_import.weeks_per_batch', 2));
            $allWeeks = collect($allRows)
                ->map(fn (array $r) => (int) ($r[ProgramCsvColumns::WEEK] ?? 0))
                ->filter(fn (int $w) => $w > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();

            foreach (array_chunk($allWeeks, $verifyBatch) as $weekBatch) {
                $subset = array_values(array_filter(
                    $allRows,
                    static fn (array $r): bool => in_array((int) ($r[ProgramCsvColumns::WEEK] ?? 0), array_map('intval', $weekBatch), true),
                ));
                foreach ($this->verifyBatchNumbers($payload, array_map('intval', $weekBatch), $subset) as $row) {
                    $verified[] = $row;
                }
            }
            $allRows = $verified !== [] ? $verified : $allRows;
        }

        return $allRows;
    }

    private function raiseExecutionLimits(): void
    {
        $seconds = max(120, (int) config('program_import.timeout_seconds', 180));
        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds + 60);
        }
        @ini_set('max_execution_time', (string) ($seconds + 60));
        @ini_set('max_input_time', (string) ($seconds + 60));
    }

    /**
     * @return array{kind: 'text'|'image', text?: string, mime?: string, base64?: string, ext: string}
     */
    private function prepareSourcePayload(UploadedFile $file, int $coachId, string $ext): array
    {
        if (in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
            return [
                'kind' => 'text',
                'ext' => $ext,
                'text' => $this->spreadsheetToText($file, $ext),
            ];
        }

        $storedPath = $file->store("program-imports/{$coachId}", 'local');
        $absolute = Storage::disk('local')->path($storedPath);
        $base64 = base64_encode((string) file_get_contents($absolute));

        // PDF natif OpenAI (type file), pas image_url — bien plus fiable qu’une capture.
        if ($ext === 'pdf') {
            $filename = $file->getClientOriginalName() ?: 'programme.pdf';
            if (! str_ends_with(strtolower($filename), '.pdf')) {
                $filename .= '.pdf';
            }

            return [
                'kind' => 'file',
                'ext' => 'pdf',
                'mime' => 'application/pdf',
                'filename' => $filename,
                'base64' => $base64,
            ];
        }

        $mime = $file->getMimeType() ?: 'image/jpeg';

        return [
            'kind' => 'image',
            'ext' => $ext,
            'mime' => $mime,
            'base64' => $base64,
        ];
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, ext: string}  $payload
     * @return list<array<string, mixed>>
     */
    private function buildInventoryContent(array $payload): array
    {
        $prompt = <<<'PROMPT'
Analyse ce programme et LISTE TOUTES les séances visibles.

Retourne UNIQUEMENT un JSON :
{
  "session_count": 20,
  "week_count": 5,
  "days_per_week": 4,
  "sessions": [
    {"week":1,"day":1,"session_number":1,"session_label":"SÉANCE 1","focus":"VOLUME","is_rest":false},
    {"week":1,"day":2,"session_number":2,"session_label":"SÉANCE 2","focus":"TECHNIQUE","is_rest":false},
    {"week":5,"day":3,"session_number":19,"session_label":"SÉANCE 19","focus":null,"is_rest":true}
  ]
}

Règles CRITIQUES :
- Une grille 5 semaines × 4 colonnes = 20 séances. Tu DOIS toutes les lister (1..20 si présentes).
- Colonnes / barres verticales "REPOS" entre les séances = séparateurs, PAS des séances.
- Une case entière marquée REPOS (ex. SÉANCE 19) = is_rest=true (quand même listée).
- week = numéro de semaine (Semaine 1..N). day = index de colonne d'entraînement 1..N (sans compter REPOS).
- Ne t'arrête PAS après les 2 premières séances. Scanne toute la grille / tout le fichier.
PROMPT;

        $inventoryPayload = $payload;
        if (($inventoryPayload['kind'] ?? '') === 'image') {
            $inventoryPayload['image_detail'] = 'low';
        }

        return $this->userContentWithSource($prompt, $inventoryPayload);
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, ext: string}  $payload
     * @param  list<array<string, mixed>>|null  $sessions
     * @return list<array<string, mixed>>
     */
    private function buildFullExtractContent(array $payload, ?array $sessions = null): array
    {
        $extra = '';
        if ($sessions !== null && $sessions !== []) {
            $extra = "\n\nSéances attendues (extraites à l'inventaire) :\n".json_encode([
                'sessions' => $sessions,
            ], JSON_UNESCAPED_UNICODE);
        }

        return $this->userContentWithSource($this->extractPrompt().$extra, $payload);
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, ext: string}  $payload
     * @param  list<int>  $weeks
     * @param  list<array<string, mixed>>  $weekSessions
     * @return list<array<string, mixed>>
     */
    private function buildWeeksExtractContent(array $payload, array $weeks, array $weekSessions): array
    {
        $labels = collect($weekSessions)
            ->map(fn (array $s) => sprintf(
                'S%d week=%d day=%d label=%s rest=%s',
                (int) ($s['session_number'] ?? 0),
                (int) ($s['week'] ?? 0),
                (int) ($s['day'] ?? 0),
                (string) ($s['session_label'] ?? ''),
                ! empty($s['is_rest']) ? 'yes' : 'no',
            ))
            ->implode(' | ');

        $weekList = implode(', ', $weeks);
        $prompt = $this->extractPrompt()."\n\n"
            ."FOCUS OBLIGATOIRE : extrais UNIQUEMENT les Semaines {$weekList}.\n"
            ."Séances concernées : {$labels}\n"
            .'Ignore les autres semaines. Pour chaque séance non-repos, extrais TOUTES les lignes EXERCICE/SÉRIES/REPS/CHARGE.';

        return $this->userContentWithSource($prompt, $payload);
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, ext: string}  $payload
     * @return list<array<string, mixed>>
     */
    private function userContentWithSource(string $prompt, array $payload): array
    {
        if ($payload['kind'] === 'text') {
            return [
                ['type' => 'text', 'text' => $prompt."\n\nContenu du fichier ({$payload['ext']}) :\n".$payload['text']],
            ];
        }

        if ($payload['kind'] === 'file') {
            return [
                ['type' => 'text', 'text' => $prompt],
                [
                    'type' => 'file',
                    'file' => [
                        'filename' => (string) ($payload['filename'] ?? 'programme.pdf'),
                        'file_data' => 'data:application/pdf;base64,'.($payload['base64'] ?? ''),
                    ],
                ],
            ];
        }

        return [
            ['type' => 'text', 'text' => $prompt],
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$payload['mime']};base64,{$payload['base64']}",
                    // high only when needed — inventory can use low for speed
                    'detail' => $payload['image_detail'] ?? 'high',
                ],
            ],
        ];
    }

    private function spreadsheetToText(UploadedFile $file, string $ext): string
    {
        if ($ext === 'xlsx') {
            try {
                [$headers, $rows] = (new ProgramXlsxReader)->read($file);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['file' => $e->getMessage()]);
            }
        } else {
            [$headers, $rows] = $this->spreadsheetImporter->readRaw($file);
        }

        $headers = array_map(fn ($h) => $this->toUtf8((string) $h), $headers);

        $lines = [];
        $lines[] = '### HEADERS';
        $lines[] = implode("\t", $headers);
        $lines[] = '### ROWS (row_index starting at 1)';
        foreach ($rows as $i => $row) {
            $cells = array_map(fn ($c) => $this->toUtf8((string) $c), $row);
            $lines[] = 'R'.($i + 1)."\t".implode("\t", $cells);
        }

        $text = $this->toUtf8(implode("\n", $lines));
        $max = (int) config('program_import.max_source_chars', 200_000);
        if (strlen($text) > $max) {
            $text = substr($text, 0, $max)."\n…[tronqué]";
        }

        return $text;
    }

    private function toUtf8(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            // Strip invalid sequences that still pass check in edge cases.
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

            return is_string($clean) ? $clean : $value;
        }

        $detected = mb_detect_encoding($value, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-15'], true);
        $converted = @mb_convert_encoding($value, 'UTF-8', $detected ?: 'Windows-1252');

        if (! is_string($converted) || $converted === '') {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value) ?: $value;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', (string) $converted);

        return is_string($clean) ? $clean : (string) $converted;
    }

    /**
     * @param  list<array<string, mixed>>  $userContent
     * @return array<string, mixed>
     */
    private function callModel(array $userContent, string $systemPrompt): array
    {
        $maxAttempts = max(1, (int) config('program_import.rate_limit_retries', 4));
        $response = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::withToken((string) config('program_import.openai_api_key'))
                    ->withOptions([
                        'verify' => (bool) config('program_import.http_verify', true),
                    ])
                    ->timeout((int) config('program_import.timeout_seconds', 180))
                    ->post((string) config('program_import.openai_endpoint'), [
                        'model' => config('program_import.openai_model'),
                        'temperature' => 0,
                        'max_tokens' => (int) config('program_import.max_tokens', 16384),
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $systemPrompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => $userContent,
                            ],
                        ],
                    ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $message = $e->getMessage();
                if (str_contains($message, 'SSL certificate') || str_contains($message, 'cURL error 60')) {
                    throw ValidationException::withMessages([
                        'file' => 'Erreur SSL locale (certificat CA). Corrigez curl.cainfo dans php.ini, ou en dev : PROGRAM_IMPORT_HTTP_VERIFY=false.',
                    ]);
                }

                throw ValidationException::withMessages([
                    'file' => 'Impossible de joindre l’API IA. Vérifiez la connexion réseau.',
                ]);
            }

            if ($response->status() === 429 && $attempt < $maxAttempts) {
                $retryAfter = (int) ($response->header('Retry-After') ?: 0);
                $sleep = max($retryAfter, min(60, 2 ** $attempt));
                sleep($sleep);
                continue;
            }

            break;
        }

        if ($response === null || ! $response->successful()) {
            $status = $response?->status() ?? 0;
            if ($status === 429) {
                throw ValidationException::withMessages([
                    'file' => 'Limite OpenAI atteinte (429). Attends 1 minute puis réessaie — l’app fait déjà moins d’appels sur CSV/XLSX.',
                ]);
            }

            throw ValidationException::withMessages([
                'file' => 'Échec de l’analyse IA ('.$status.').',
            ]);
        }

        $finish = data_get($response->json(), 'choices.0.finish_reason');
        $text = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Réponse IA vide.');
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            if ($finish === 'length') {
                throw new ProgramAiResponseTruncated('Réponse IA tronquée (trop longue).');
            }

            throw ValidationException::withMessages([
                'file' => 'Impossible d’interpréter la réponse IA.',
            ]);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $inventory
     * @return list<array{week:int,day:int,session_number:?int,session_label:string,focus:?string,is_rest:bool}>
     */
    private function normalizeSessionInventory(array $inventory): array
    {
        $sessions = $inventory['sessions'] ?? null;
        if (! is_array($sessions)) {
            return [];
        }

        $normalized = [];
        foreach ($sessions as $session) {
            if (! is_array($session)) {
                continue;
            }

            $week = (int) ($session['week'] ?? 0);
            $day = (int) ($session['day'] ?? 0);
            if ($week < 1 || $day < 1 || $day > 7) {
                continue;
            }

            $normalized[] = [
                'week' => $week,
                'day' => $day,
                'session_number' => isset($session['session_number']) ? (int) $session['session_number'] : null,
                'session_label' => trim((string) ($session['session_label'] ?? "Jour {$day}")),
                'focus' => isset($session['focus']) ? (string) $session['focus'] : null,
                'is_rest' => (bool) ($session['is_rest'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * @param  mixed  $rawRows
     * @return list<array<string, mixed>>
     */
    private function mapRawRows(mixed $rawRows): array
    {
        if (! is_array($rawRows)) {
            return [];
        }

        $rows = [];
        foreach ($rawRows as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            // Skip pure rest markers without exercises.
            $variant = trim((string) ($raw['variant_name'] ?? $raw['exercise'] ?? ''));
            if ($variant === '' || preg_match('/^repos$/iu', $variant)) {
                continue;
            }

            $rows[] = [
                ProgramCsvColumns::WEEK => $raw['week'] ?? '',
                ProgramCsvColumns::DAY => $raw['day'] ?? '',
                ProgramCsvColumns::SECTION => $raw['section'] ?? 'accessory',
                ProgramCsvColumns::EXERCISE => $variant,
                // Prefer exact cell strings — numeric fields are fallbacks only.
                ProgramCsvColumns::SETS => $raw['sets_raw'] ?? $raw['sets'] ?? '',
                ProgramCsvColumns::REPS => $raw['reps_raw'] ?? $raw['reps'] ?? '',
                'sets_raw' => $raw['sets_raw'] ?? $raw['sets'] ?? '',
                'reps_raw' => $raw['reps_raw'] ?? $raw['reps'] ?? '',
                ProgramCsvColumns::LOAD => $raw['load'] ?? '',
                ProgramCsvColumns::LOAD_PERCENT => $raw['load_percent'] ?? '',
                ProgramCsvColumns::RPE => $raw['rpe'] ?? '',
                ProgramCsvColumns::REST_SECONDS => $raw['rest_seconds'] ?? '',
                ProgramCsvColumns::MAIN_LIFT => $raw['main_lift'] ?? $raw['lift'] ?? '',
                ProgramCsvColumns::SESSION_LABEL => $raw['session_label'] ?? '',
                ProgramCsvColumns::NOTES => $raw['notes'] ?? '',
                'parent_name' => $raw['parent_name'] ?? $raw['pass_lift'] ?? '',
                'variant_name' => $variant,
                'category' => $raw['category'] ?? '',
                'charge_raw' => $raw['charge_raw'] ?? $raw['charge'] ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $primary
     * @param  list<array<string, mixed>>  $extra
     * @return list<array<string, mixed>>
     */
    private function mergeRows(array $primary, array $extra): array
    {
        $keys = [];
        $merged = [];

        foreach (array_merge($primary, $extra) as $row) {
            $key = implode('|', [
                (string) ($row[ProgramCsvColumns::WEEK] ?? ''),
                (string) ($row[ProgramCsvColumns::DAY] ?? ''),
                (string) ($row['variant_name'] ?? ''),
                (string) ($row[ProgramCsvColumns::SETS] ?? ''),
                (string) ($row[ProgramCsvColumns::REPS] ?? ''),
                (string) ($row['charge_raw'] ?? $row[ProgramCsvColumns::LOAD] ?? ''),
            ]);
            if (isset($keys[$key])) {
                continue;
            }
            $keys[$key] = true;
            $merged[] = $row;
        }

        return $merged;
    }

    private function inventorySystemPrompt(): string
    {
        return 'Tu inventories des programmes powerlifting en grille. Tu listes TOUTES les séances sans exception. JSON uniquement.';
    }

    private function extractSystemPrompt(): string
    {
        return 'Tu extrais des programmes de force avec une précision numérique absolue. '
            .'Tu recopies EXACTEMENT les chiffres des cellules (12 ≠ 10, 167,5 ≠ 160, 72,5% ≠ 70%). '
            .'JSON uniquement, exhaustif.';
    }

    private function extractPrompt(): string
    {
        return <<<'PROMPT'
Extrais les lignes d'exercices du programme.

Retourne UNIQUEMENT un JSON :
{
  "rows": [
    {
      "week": 1,
      "day": 1,
      "section": "topset",
      "parent_name": "Squat",
      "variant_name": "Squat",
      "pass_lift": "Squat",
      "lift": "squat",
      "category": "main_lift",
      "sets_raw": "3",
      "reps_raw": "12",
      "charge_raw": "167,5kg",
      "sets": 3,
      "reps": 12,
      "load": null,
      "load_percent": null,
      "rpe": null,
      "rest_seconds": null,
      "main_lift": "squat",
      "session_label": "SÉANCE 1",
      "notes": null
    }
  ]
}

PRÉCISION NUMÉRIQUE (CRITIQUE) :
- sets_raw / reps_raw / charge_raw = texte EXACT de la cellule, sans reformater.
- Ne jamais approximer : 12 ≠ 10, 8 ≠ 6, 167,5 ≠ 167 ≠ 160, 72,5%RM ≠ 70%RM, RPE8 ≠ RPE6.
- Relis chaque chiffre caractère par caractère avant d'écrire le JSON.
- Si la cellule indique "12", mets "12" (pas 10). Si "167,5kg", mets "167,5kg" (virgule OK).
- Laisse load/load_percent/rpe à null : on les dérivera de charge_raw côté serveur.
- Plage "10-12" : mets sets_raw/reps_raw = "10-12" tel quel (ne remplace pas par 10).

Autres règles :
1) Extraire CHAQUE séance avec exercices. Ne jamais s'arrêter après SÉANCE 1–2.
2) Colonnes REPOS entre séances = séparateurs. Case SÉANCE entièrement REPOS = aucune row.
3) day = index de séance dans la semaine ; week = Semaine N.
4) Une row = une ligne d'exercice (Squat 1×8 puis 3×8 = 2 rows).
5) parent_name = mouvement parent ; variant_name = nom exact (DC, SDT Deficit…).
6) Abréviations : DC→bench, SDT→deadlift, OHP→general/bench.
7) section : 1er mouvement principal = topset, suite même lift = backoff, sinon accessory.
8) N'invente rien.
PROMPT;
    }

    /**
     * Second pass: ask the model to correct digit errors against the source.
     *
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, ext: string}  $payload
     * @param  list<int>  $weeks
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function verifyBatchNumbers(array $payload, array $weeks, array $rows): array
    {
        $compact = array_map(static function (array $row): array {
            return [
                'week' => $row[ProgramCsvColumns::WEEK] ?? null,
                'day' => $row[ProgramCsvColumns::DAY] ?? null,
                'variant_name' => $row['variant_name'] ?? null,
                'sets_raw' => $row['sets_raw'] ?? $row[ProgramCsvColumns::SETS] ?? null,
                'reps_raw' => $row['reps_raw'] ?? $row[ProgramCsvColumns::REPS] ?? null,
                'charge_raw' => $row['charge_raw'] ?? null,
                'session_label' => $row[ProgramCsvColumns::SESSION_LABEL] ?? null,
            ];
        }, $rows);

        $weekList = implode(', ', $weeks);
        $prompt = <<<PROMPT
Vérifie et CORRIGE uniquement les chiffres (séries, reps, charge) pour les Semaines {$weekList}.

Voici l'extraction actuelle (peut contenir des erreurs OCR du type 10 au lieu de 12, 70% au lieu de 72,5%) :
```json
{$this->jsonEncode($compact)}
```

Retourne UNIQUEMENT un JSON :
{"rows":[{"week":1,"day":1,"variant_name":"Squat","sets_raw":"3","reps_raw":"12","charge_raw":"167,5kg","session_label":"SÉANCE 1", ...toutes les autres clés utiles...}]}

Règles :
- Compare chaque ligne au tableau source (image/fichier).
- Corrige sets_raw, reps_raw, charge_raw si le chiffre lu est faux.
- Ne change pas la structure (ne supprime/ajoute une ligne que si clairement manquante/extra).
- Recopie le texte EXACT des cellules. 12≠10, 167,5kg≠160kg, 72,5%RM≠70%RM.
PROMPT;

        // Keep prior fields; overlay corrections from verify response.
        $decoded = $this->callModel(
            $this->userContentWithSource($prompt, $payload),
            'Tu es un correcteur de chiffres OCR pour programmes de musculation. JSON uniquement.',
        );

        $corrected = $this->mapRawRows($decoded['rows'] ?? []);
        if ($corrected === []) {
            return $rows;
        }

        return $this->overlayNumberCorrections($rows, $corrected);
    }

    /**
     * @param  list<array<string, mixed>>  $original
     * @param  list<array<string, mixed>>  $corrected
     * @return list<array<string, mixed>>
     */
    private function overlayNumberCorrections(array $original, array $corrected): array
    {
        $byKey = [];
        foreach ($corrected as $row) {
            $key = mb_strtolower(implode('|', [
                (string) ($row[ProgramCsvColumns::WEEK] ?? ''),
                (string) ($row[ProgramCsvColumns::DAY] ?? ''),
                (string) ($row['variant_name'] ?? ''),
                (string) ($row[ProgramCsvColumns::SESSION_LABEL] ?? ''),
            ]));
            $byKey[$key] = $row;
        }

        $out = [];
        foreach ($original as $row) {
            $key = mb_strtolower(implode('|', [
                (string) ($row[ProgramCsvColumns::WEEK] ?? ''),
                (string) ($row[ProgramCsvColumns::DAY] ?? ''),
                (string) ($row['variant_name'] ?? ''),
                (string) ($row[ProgramCsvColumns::SESSION_LABEL] ?? ''),
            ]));

            if (! isset($byKey[$key])) {
                $out[] = $row;
                continue;
            }

            $fix = $byKey[$key];
            foreach (['sets_raw', 'reps_raw', 'charge_raw', ProgramCsvColumns::SETS, ProgramCsvColumns::REPS] as $field) {
                if (isset($fix[$field]) && trim((string) $fix[$field]) !== '') {
                    $row[$field] = $fix[$field];
                }
            }
            $out[] = $row;
        }

        // If verify found additional lines, append unmatched corrections.
        foreach ($corrected as $row) {
            $key = mb_strtolower(implode('|', [
                (string) ($row[ProgramCsvColumns::WEEK] ?? ''),
                (string) ($row[ProgramCsvColumns::DAY] ?? ''),
                (string) ($row['variant_name'] ?? ''),
                (string) ($row[ProgramCsvColumns::SESSION_LABEL] ?? ''),
            ]));
            $found = false;
            foreach ($original as $o) {
                $ok = mb_strtolower(implode('|', [
                    (string) ($o[ProgramCsvColumns::WEEK] ?? ''),
                    (string) ($o[ProgramCsvColumns::DAY] ?? ''),
                    (string) ($o['variant_name'] ?? ''),
                    (string) ($o[ProgramCsvColumns::SESSION_LABEL] ?? ''),
                ]));
                if ($ok === $key) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $out[] = $row;
            }
        }

        return $out;
    }

    private function jsonEncode(mixed $data): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $encoded = json_encode($data, $flags);
        if ($encoded === false) {
            // Last resort: recursively force UTF-8.
            $encoded = json_encode($this->utf8ize($data), $flags | (defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0));
        }

        return is_string($encoded) ? $encoded : '[]';
    }

    private function utf8ize(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->toUtf8($value);
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$this->utf8ize($k)] = $this->utf8ize($v);
            }

            return $out;
        }

        return $value;
    }
}

class ProgramAiResponseTruncated extends RuntimeException
{
}
