<?php

namespace App\Support\ProgramImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProgramVisionImporter
{
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
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'file' => 'L’import photo n’est pas configuré. Ajoutez PROGRAM_IMPORT_OPENAI_API_KEY dans l’environnement.',
            ]);
        }

        $storedPath = $file->store("program-imports/{$coachId}", 'local');
        $absolute = Storage::disk('local')->path($storedPath);
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode((string) file_get_contents($absolute));
        $ext = strtolower((string) $file->getClientOriginalExtension());

        $schemaHint = implode(', ', ProgramCsvColumns::official());
        $prompt = <<<PROMPT
Tu extrais un programme de force (powerlifting / musculation) depuis l'image ou le PDF.
Retourne UNIQUEMENT un JSON valide de la forme:
{"rows":[{"week":1,"day":1,"section":"topset","exercise":"Squat","sets":1,"reps":3,"load":null,"load_percent":85,"rpe":8,"rest_seconds":180,"main_lift":"squat","session_label":"Jour 1","notes":null}]}

Règles:
- Colonnes possibles: {$schemaHint}
- section ∈ topset|backoff|accessory|warmup (défaut accessory)
- main_lift ∈ squat|bench|deadlift si identifiable
- day = jour de la semaine 1..7
- Une ligne = un exercice
- Ignore les échauffements génériques non chiffrés
- Si une valeur est absente, utilise null
- N'invente pas de séances illisibles
PROMPT;

        $content = [
            ['type' => 'text', 'text' => $prompt],
        ];

        if ($ext === 'pdf' || str_contains((string) $mime, 'pdf')) {
            $filename = $file->getClientOriginalName() ?: 'programme.pdf';
            $content[] = [
                'type' => 'file',
                'file' => [
                    'filename' => $filename,
                    'file_data' => "data:application/pdf;base64,{$base64}",
                ],
            ];
        } else {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$mime};base64,{$base64}",
                ],
            ];
        }
        try {
            $response = Http::withToken((string) config('program_import.openai_api_key'))
                ->withOptions([
                    'verify' => (bool) config('program_import.http_verify', true),
                ])
                ->timeout(90)
                ->post((string) config('program_import.openai_endpoint'), [
                    'model' => config('program_import.openai_model'),
                    'temperature' => 0.1,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un extracteur de programmes d’entraînement. Tu réponds uniquement en JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'SSL certificate') || str_contains($message, 'cURL error 60')) {
                throw ValidationException::withMessages([
                    'file' => 'Erreur SSL locale (certificat CA). Corrigez curl.cainfo dans php.ini, ou en dev uniquement ajoutez PROGRAM_IMPORT_HTTP_VERIFY=false dans .env.',
                ]);
            }

            throw ValidationException::withMessages([
                'file' => 'Impossible de joindre l’API Vision. Vérifiez la connexion réseau.',
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'file' => 'Échec de l’extraction Vision ('.$response->status().'). Réessayez ou utilisez un CSV.',
            ]);
        }

        $text = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Réponse Vision vide.');
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'file' => 'Impossible d’interpréter le programme extrait de la photo.',
            ]);
        }

        $rawRows = $decoded['rows'] ?? $decoded;
        if (! is_array($rawRows)) {
            throw ValidationException::withMessages([
                'file' => 'Aucun exercice détecté sur la photo.',
            ]);
        }

        $rows = [];
        foreach ($rawRows as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $rows[] = [
                ProgramCsvColumns::WEEK => $raw['week'] ?? '',
                ProgramCsvColumns::DAY => $raw['day'] ?? '',
                ProgramCsvColumns::SECTION => $raw['section'] ?? 'accessory',
                ProgramCsvColumns::EXERCISE => $raw['exercise'] ?? '',
                ProgramCsvColumns::SETS => $raw['sets'] ?? '',
                ProgramCsvColumns::REPS => $raw['reps'] ?? '',
                ProgramCsvColumns::LOAD => $raw['load'] ?? '',
                ProgramCsvColumns::LOAD_PERCENT => $raw['load_percent'] ?? '',
                ProgramCsvColumns::RPE => $raw['rpe'] ?? '',
                ProgramCsvColumns::REST_SECONDS => $raw['rest_seconds'] ?? '',
                ProgramCsvColumns::MAIN_LIFT => $raw['main_lift'] ?? '',
                ProgramCsvColumns::SESSION_LABEL => $raw['session_label'] ?? '',
                ProgramCsvColumns::NOTES => $raw['notes'] ?? '',
            ];
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Aucun exercice détecté sur la photo.',
            ]);
        }

        return $rows;
    }
}
