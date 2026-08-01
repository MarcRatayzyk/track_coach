<?php

namespace App\Support\ProgramImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Même procédé que l’import JSON manuel :
 * 1) L’IA remplit track_coach_program_v1 (prompt unique modulable)
 * 2) ProgramJsonImportNormalizer → rows draft
 */
class ProgramAiImporter
{
    public function __construct(
        private readonly ProgramCsvImporter $spreadsheetImporter = new ProgramCsvImporter,
        private readonly ProgramJsonImportNormalizer $jsonNormalizer = new ProgramJsonImportNormalizer,
    ) {}

    public function isConfigured(): bool
    {
        $key = config('program_import.openai_api_key');

        return is_string($key) && trim($key) !== '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function extractRows(UploadedFile $file, int $coachId, int $weekCount = 0): array
    {
        $this->raiseExecutionLimits();

        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'file' => 'L’import IA n’est pas configuré. Ajoutez PROGRAM_IMPORT_OPENAI_API_KEY dans .env.',
            ]);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $payload = $this->prepareSourcePayload($file, $coachId, $ext);

        try {
            $programJson = $this->callModel(
                $this->userContentWithSource(
                    $this->jsonNormalizer->externalAiPrompt($weekCount),
                    $payload,
                ),
                $this->systemPrompt(),
            );
        } catch (ProgramAiResponseTruncated) {
            $programJson = $this->extractProgramJsonByWeeks($payload, $weekCount > 0 ? $weekCount : 8);
        }

        try {
            $rows = $this->jsonNormalizer->normalize($programJson);
        } catch (\InvalidArgumentException $e) {
            // JSON incomplet → découpe semaine par semaine puis fusionne.
            $programJson = $this->extractProgramJsonByWeeks(
                $payload,
                max(1, (int) ($programJson['detected']['week_count'] ?? $weekCount ?: 5)),
            );
            try {
                $rows = $this->jsonNormalizer->normalize($programJson);
            } catch (\InvalidArgumentException $inner) {
                throw ValidationException::withMessages([
                    'file' => $inner->getMessage(),
                ]);
            }
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Aucun exercice détecté dans le fichier.',
            ]);
        }

        return $rows;
    }

    private function systemPrompt(): string
    {
        return 'Tu extrais des programmes de musculation / powerlifting. '
            .'Tu remplis uniquement le JSON track_coach_program_v1 demandé. '
            .'Fidélité maximale : aucun calcul, aucune invention. JSON uniquement.';
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, filename?: string, ext: string}  $payload
     * @return array<string, mixed>
     */
    private function extractProgramJsonByWeeks(array $payload, int $maxWeeks): array
    {
        $maxWeeks = max(1, min(16, $maxWeeks));
        $allWeeks = [];
        $detected = null;

        for ($week = 1; $week <= $maxWeeks; $week++) {
            try {
                $part = $this->callModel(
                    $this->userContentWithSource(
                        $this->jsonNormalizer->weekFocusPrompt($week),
                        $payload,
                    ),
                    $this->systemPrompt(),
                );
            } catch (\Throwable) {
                if ($allWeeks !== []) {
                    break;
                }
                continue;
            }

            if ($detected === null && isset($part['detected']) && is_array($part['detected'])) {
                $detected = $part['detected'];
            }

            $weeks = $part['weeks'] ?? null;
            if (! is_array($weeks) || $weeks === []) {
                if ($allWeeks !== []) {
                    break;
                }
                continue;
            }

            $hasExercises = false;
            foreach ($weeks as $weekBlock) {
                if (! is_array($weekBlock)) {
                    continue;
                }
                $weekBlock['week'] = (int) ($weekBlock['week'] ?? $week);
                $sessions = $weekBlock['sessions'] ?? [];
                if (is_array($sessions)) {
                    foreach ($sessions as $session) {
                        if (is_array($session) && ! empty($session['exercises']) && is_array($session['exercises'])) {
                            $hasExercises = $hasExercises || count($session['exercises']) > 0;
                        }
                    }
                }
                $allWeeks[] = $weekBlock;
            }

            if (! $hasExercises && $allWeeks !== []) {
                // Semaine vide après du contenu → fin probable.
                array_pop($allWeeks);
                break;
            }
        }

        return [
            'format' => ProgramJsonImportNormalizer::FORMAT,
            'detected' => $detected ?? [
                'week_count' => count($allWeeks),
                'days_per_week_typical' => null,
                'layout' => 'extracted_by_week',
            ],
            'weeks' => $allWeeks,
        ];
    }

    private function raiseExecutionLimits(): void
    {
        $seconds = max(120, (int) config('program_import.timeout_seconds', 180));
        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds + 120);
        }
        @ini_set('max_execution_time', (string) ($seconds + 120));
        @ini_set('max_input_time', (string) ($seconds + 120));
    }

    /**
     * @return array{kind: string, text?: string, mime?: string, base64?: string, filename?: string, ext: string}
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

        return [
            'kind' => 'image',
            'ext' => $ext,
            'mime' => $file->getMimeType() ?: 'image/jpeg',
            'base64' => $base64,
        ];
    }

    private function spreadsheetToText(UploadedFile $file, string $ext): string
    {
        $max = (int) config('program_import.max_source_chars', 200_000);

        if ($ext === 'xlsx') {
            try {
                $text = (new ProgramXlsxReader)->toFaithfulText($file, $max);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['file' => $e->getMessage()]);
            }

            return $this->toUtf8($text);
        }

        [$headers, $rows] = $this->spreadsheetImporter->readRaw($file);
        $headers = array_map(fn ($h) => $this->toUtf8((string) $h), $headers);

        $lines = [];
        $lines[] = '### CSV/TXT';
        $lines[] = '### HEADERS';
        $lines[] = implode("\t", $headers);
        $lines[] = '### ROWS';
        foreach ($rows as $i => $row) {
            $cells = array_map(fn ($c) => $this->toUtf8((string) $c), $row);
            $lines[] = 'R'.($i + 1)."\t".implode("\t", $cells);
        }

        $text = $this->toUtf8(implode("\n", $lines));
        if (strlen($text) > $max) {
            $text = substr($text, 0, $max)."\n…[tronqué]";
        }

        return $text;
    }

    /**
     * @param  array{kind: string, text?: string, mime?: string, base64?: string, filename?: string, ext: string}  $payload
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
                    'detail' => $payload['image_detail'] ?? 'high',
                ],
            ],
        ];
    }

    /**
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
                    'file' => 'Limite OpenAI atteinte (429). Attends 1 minute puis réessaie.',
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

    private function toUtf8(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
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
}

class ProgramAiResponseTruncated extends RuntimeException
{
}
