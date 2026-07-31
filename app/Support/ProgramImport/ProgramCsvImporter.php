<?php

namespace App\Support\ProgramImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProgramCsvImporter
{
    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    public function readRaw(UploadedFile $file): array
    {
        return $this->readTable($file);
    }

    /**
     * @return array{
     *   mode: 'structured'|'needs_mapping',
     *   headers: list<string>,
     *   sample_rows: list<list<string>>,
     *   suggested_mapping: array<string, string|null>,
     *   rows: list<array<string, mixed>>,
     *   raw_row_count: int
     * }
     */
    public function inspect(UploadedFile $file): array
    {
        [$headers, $dataRows] = $this->readTable($file);

        if ($headers === []) {
            throw ValidationException::withMessages([
                'file' => 'Le fichier est vide ou illisible.',
            ]);
        }

        $normalizedHeaders = array_map(fn (string $h) => $this->normalizeHeader($h), $headers);
        $suggested = $this->suggestMapping($normalizedHeaders, $headers);

        if ($this->isOfficialStructure($normalizedHeaders)) {
            $rows = [];
            foreach ($dataRows as $dataRow) {
                $assoc = [];
                foreach (ProgramCsvColumns::official() as $i => $column) {
                    $assoc[$column] = $dataRow[$i] ?? '';
                }
                $rows[] = $assoc;
            }

            return [
                'mode' => 'structured',
                'headers' => $headers,
                'sample_rows' => array_slice($dataRows, 0, 5),
                'suggested_mapping' => $suggested,
                'rows' => $rows,
                'raw_row_count' => count($dataRows),
            ];
        }

        return [
            'mode' => 'needs_mapping',
            'headers' => $headers,
            'sample_rows' => array_slice($dataRows, 0, 5),
            'suggested_mapping' => $suggested,
            'rows' => [],
            'raw_row_count' => count($dataRows),
        ];
    }

    /**
     * @param  array<string, string|null>  $mapping  field => source header name
     * @return list<array<string, mixed>>
     */
    public function applyMapping(UploadedFile $file, array $mapping): array
    {
        [$headers, $dataRows] = $this->readTable($file);
        $headerIndex = [];
        foreach ($headers as $i => $header) {
            $headerIndex[$header] = $i;
            $headerIndex[$this->normalizeHeader($header)] = $i;
        }

        foreach (['week', 'day', 'exercise', 'sets', 'reps'] as $field) {
            $source = $mapping[$field] ?? null;
            if (! is_string($source) || trim($source) === '') {
                throw ValidationException::withMessages([
                    'mapping' => "Le champ « {$field} » doit être mappé à une colonne.",
                ]);
            }
        }

        $rows = [];
        foreach ($dataRows as $dataRow) {
            $assoc = [];
            foreach (ProgramCsvColumns::official() as $field) {
                $sourceHeader = $mapping[$field] ?? null;
                if (! is_string($sourceHeader) || $sourceHeader === '') {
                    $assoc[$field] = '';
                    continue;
                }

                $idx = $headerIndex[$sourceHeader]
                    ?? $headerIndex[$this->normalizeHeader($sourceHeader)]
                    ?? null;

                $assoc[$field] = $idx === null ? '' : (string) ($dataRow[$idx] ?? '');
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function readTable(UploadedFile $file): array
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx'], true)) {
            try {
                return (new ProgramXlsxReader)->read($file);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages([
                    'file' => $e->getMessage(),
                ]);
            }
        }

        if (in_array($ext, ['xls'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Le format .xls (Excel 97–2003) n’est pas supporté. Enregistrez en .xlsx ou CSV.',
            ]);
        }

        return $this->readCsv($file);
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function readCsv(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath() ?: '');
        if ($contents === false || $contents === '') {
            throw ValidationException::withMessages([
                'file' => 'Impossible de lire le fichier uploadé.',
            ]);
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        $contents = $this->normalizeCsvEncoding($contents);

        $firstLine = strtok($contents, "\n") ?: $contents;
        $delimiter = $this->detectDelimiter($firstLine);

        $handle = fopen('php://temp', 'r+b');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Impossible d’ouvrir le fichier CSV.',
            ]);
        }

        try {
            fwrite($handle, $contents);
            rewind($handle);

            $headers = fgetcsv($handle, 0, $delimiter);
            if ($headers === false) {
                return [[], []];
            }

            $headers = array_map(
                fn ($h) => trim($this->normalizeCsvEncoding((string) $h)),
                $headers,
            );

            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }
                $rows[] = array_map(
                    fn ($cell) => trim($this->normalizeCsvEncoding((string) $cell)),
                    $row,
                );
            }

            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',' => 0, ';' => 0, "\t" => 0];
        foreach (array_keys($candidates) as $delimiter) {
            $candidates[$delimiter] = substr_count($line, $delimiter);
        }
        arsort($candidates);
        $best = array_key_first($candidates);

        return ($candidates[$best] ?? 0) > 0 ? (string) $best : ',';
    }

    /**
     * @param  list<string>  $normalizedHeaders
     */
    private function isOfficialStructure(array $normalizedHeaders): bool
    {
        $official = array_map(
            fn (string $h) => $this->normalizeHeader($h),
            ProgramCsvColumns::official(),
        );

        if (count($normalizedHeaders) < count($official)) {
            return false;
        }

        foreach ($official as $i => $expected) {
            if (($normalizedHeaders[$i] ?? null) !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $normalizedHeaders
     * @param  list<string>  $originalHeaders
     * @return array<string, string|null>
     */
    private function suggestMapping(array $normalizedHeaders, array $originalHeaders): array
    {
        $mapping = [];
        foreach (ProgramCsvColumns::aliases() as $field => $aliases) {
            $mapping[$field] = null;
            foreach ($normalizedHeaders as $i => $normalized) {
                if (in_array($normalized, $aliases, true)) {
                    $mapping[$field] = $originalHeaders[$i];
                    break;
                }
            }
        }

        return $mapping;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = strtr($header, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            '%' => 'percent',
        ]);
        $header = preg_replace('/[^a-z0-9]+/u', '_', $header) ?? $header;

        return trim($header, '_');
    }

    /**
     * Excel/Windows CSV is often Windows-1252 or ISO-8859-1 — normalize to UTF-8 for JSON.
     */
    private function normalizeCsvEncoding(string $value): string
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

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
