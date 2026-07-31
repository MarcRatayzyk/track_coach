<?php

namespace App\Support\ProgramImport;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Minimal XLSX reader (first worksheet) without PhpSpreadsheet / ext-gd.
 */
class ProgramXlsxReader
{
    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    public function read(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false || ! is_readable($path)) {
            throw new InvalidArgumentException('Impossible de lire le fichier Excel.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Fichier XLSX invalide ou corrompu.');
        }

        try {
            $sharedStrings = $this->parseSharedStrings($zip);
            $sheetPath = $this->resolveFirstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new InvalidArgumentException('Impossible de lire la première feuille du classeur.');
            }

            $grid = $this->parseSheetGrid($sheetXml, $sharedStrings);
            if ($grid === []) {
                return [[], []];
            }

            $headers = array_shift($grid) ?? [];
            $headers = array_map(static fn ($h) => trim((string) $h), $headers);

            $rows = [];
            foreach ($grid as $row) {
                $normalized = array_map(static fn ($cell) => trim((string) $cell), $row);
                if ($this->rowIsEmpty($normalized)) {
                    continue;
                }
                $rows[] = $normalized;
            }

            return [$headers, $rows];
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<string>
     */
    private function parseSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sx = $this->loadXml($xml);
        if ($sx === null) {
            return [];
        }

        $strings = [];
        foreach ($sx->si as $si) {
            $strings[] = $this->collectText($si);
        }

        return $strings;
    }

    private function resolveFirstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook === false || $rels === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $wb = $this->loadXml($workbook);
        $relXml = $this->loadXml($rels);
        if ($wb === null || $relXml === null) {
            return 'xl/worksheets/sheet1.xml';
        }

        $first = $wb->sheets->sheet[0] ?? null;
        if ($first === null) {
            return 'xl/worksheets/sheet1.xml';
        }

        $rid = (string) ($first['id'] ?? '');
        if ($rid === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        foreach ($relXml->Relationship as $rel) {
            if ((string) $rel['Id'] === $rid) {
                $target = ltrim((string) $rel['Target'], '/');
                if (! str_starts_with($target, 'xl/')) {
                    $target = 'xl/'.$target;
                }

                return $target;
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<list<string>>
     */
    private function parseSheetGrid(string $sheetXml, array $sharedStrings): array
    {
        $sx = $this->loadXml($sheetXml);
        if ($sx === null) {
            throw new InvalidArgumentException('Feuille Excel illisible.');
        }

        if (! isset($sx->sheetData->row)) {
            return [];
        }

        $grid = [];
        $maxCol = 0;

        foreach ($sx->sheetData->row as $rowXml) {
            $rowIndex = ((int) $rowXml['r']) - 1;
            if ($rowIndex < 0) {
                continue;
            }

            while (count($grid) <= $rowIndex) {
                $grid[] = [];
            }

            foreach ($rowXml->c as $cell) {
                $ref = (string) $cell['r'];
                $colIndex = $this->columnIndexFromRef($ref);
                $maxCol = max($maxCol, $colIndex);
                $grid[$rowIndex][$colIndex] = $this->cellValue($cell, $sharedStrings);
            }
        }

        $normalized = [];
        foreach ($grid as $row) {
            $line = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $line[] = $row[$c] ?? '';
            }
            $normalized[] = $line;
        }

        return $normalized;
    }

    private function columnIndexFromRef(string $ref): int
    {
        if (! preg_match('/^([A-Z]+)/i', $ref, $m)) {
            return 0;
        }

        $letters = strtoupper($m[1]);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');
        $raw = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            $idx = (int) $raw;

            return $sharedStrings[$idx] ?? '';
        }

        if ($type === 'inlineStr') {
            return $this->collectText($cell);
        }

        if ($type === 'b') {
            return $raw === '1' ? '1' : '0';
        }

        return $raw;
    }

    private function collectText(SimpleXMLElement $node): string
    {
        $value = '';

        if (isset($node->t)) {
            foreach ($node->t as $t) {
                $value .= (string) $t;
            }
        }

        if (isset($node->r)) {
            foreach ($node->r as $run) {
                if (isset($run->t)) {
                    foreach ($run->t as $t) {
                        $value .= (string) $t;
                    }
                }
            }
        }

        if ($value === '' && isset($node->is)) {
            return $this->collectText($node->is);
        }

        return $value;
    }

    private function loadXml(string $xml): ?SimpleXMLElement
    {
        // Default OOXML namespaces break SimpleXML children()/xpath reliably — strip them.
        $xml = preg_replace('/\sxmlns(:\w+)?="[^"]*"/', '', $xml) ?? $xml;
        $xml = preg_replace('/(<\/?)(\w+):([^>\s\/]+)/', '$1$3', $xml) ?? $xml;

        $previous = libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $sx instanceof SimpleXMLElement ? $sx : null;
    }

    /**
     * @param  list<string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
