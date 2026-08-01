<?php

namespace App\Support\ProgramImport;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Minimal XLSX reader without PhpSpreadsheet / ext-gd.
 * Lit toutes les feuilles, applique les cellules fusionnées, conserve les vides structurants.
 */
class ProgramXlsxReader
{
    /**
     * Compat : première feuille → headers + rows (première ligne = headers).
     *
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    public function read(UploadedFile $file): array
    {
        $sheets = $this->readAllSheets($file);
        if ($sheets === []) {
            return [[], []];
        }

        $grid = $sheets[0]['grid'];
        if ($grid === []) {
            return [[], []];
        }

        $headers = array_map(static fn ($h) => trim((string) $h), array_shift($grid) ?? []);
        $rows = [];
        foreach ($grid as $row) {
            $normalized = array_map(static fn ($cell) => trim((string) $cell), $row);
            if ($this->rowIsEmpty($normalized)) {
                continue;
            }
            $rows[] = $normalized;
        }

        return [$headers, $rows];
    }

    /**
     * @return list<array{name: string, grid: list<list<string>>}>
     */
    public function readAllSheets(UploadedFile $file): array
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
            $sheetRefs = $this->resolveAllSheetPaths($zip);
            $sheets = [];

            foreach ($sheetRefs as $ref) {
                $sheetXml = $zip->getFromName($ref['path']);
                if ($sheetXml === false) {
                    continue;
                }

                $grid = $this->parseSheetGrid($sheetXml, $sharedStrings);
                $merges = $this->parseMergeRanges($sheetXml);
                if ($merges !== []) {
                    $grid = $this->applyMerges($grid, $merges);
                }

                $sheets[] = [
                    'name' => $ref['name'],
                    'grid' => $grid,
                ];
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    /**
     * Représentation textuelle fidèle multi-feuilles pour l'étape IA.
     */
    public function toFaithfulText(UploadedFile $file, int $maxChars = 200_000): string
    {
        $sheets = $this->readAllSheets($file);
        $blocks = [];
        $blocks[] = '### XLSX — '.count($sheets).' feuille(s)';
        $blocks[] = 'Ordre de lecture : haut → bas, gauche → droite. Cellules fusionnées déjà expansées.';
        $blocks[] = 'Ne pas fusionner les tableaux de feuilles différentes.';

        foreach ($sheets as $index => $sheet) {
            $name = $sheet['name'] !== '' ? $sheet['name'] : ('Sheet'.($index + 1));
            $blocks[] = '';
            $blocks[] = '### FEUILLE: '.$name;
            $grid = $sheet['grid'];
            if ($grid === []) {
                $blocks[] = '(vide)';
                continue;
            }

            $maxCol = 0;
            foreach ($grid as $row) {
                $maxCol = max($maxCol, count($row) - 1);
            }

            foreach ($grid as $r => $row) {
                $cells = [];
                for ($c = 0; $c <= $maxCol; $c++) {
                    $cells[] = (string) ($row[$c] ?? '');
                }
                // Garder les lignes vides qui structurent (séparateurs entre tableaux).
                $blocks[] = 'R'.($r + 1)."\t".implode("\t", $cells);
            }
        }

        $text = implode("\n", $blocks);
        if (strlen($text) > $maxChars) {
            $text = substr($text, 0, $maxChars)."\n…[tronqué]";
        }

        return $text;
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

    /**
     * @return list<array{name: string, path: string}>
     */
    private function resolveAllSheetPaths(ZipArchive $zip): array
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook === false || $rels === false) {
            return [['name' => 'Sheet1', 'path' => 'xl/worksheets/sheet1.xml']];
        }

        $wb = $this->loadXml($workbook);
        $relXml = $this->loadXml($rels);
        if ($wb === null || $relXml === null || ! isset($wb->sheets->sheet)) {
            return [['name' => 'Sheet1', 'path' => 'xl/worksheets/sheet1.xml']];
        }

        $ridToTarget = [];
        foreach ($relXml->Relationship as $rel) {
            $ridToTarget[(string) $rel['Id']] = (string) $rel['Target'];
        }

        $out = [];
        foreach ($wb->sheets->sheet as $sheet) {
            $name = (string) ($sheet['name'] ?? 'Sheet');
            $rid = (string) ($sheet['id'] ?? '');
            $target = $ridToTarget[$rid] ?? '';
            if ($target === '') {
                continue;
            }
            $target = ltrim($target, '/');
            if (! str_starts_with($target, 'xl/')) {
                $target = 'xl/'.$target;
            }
            $out[] = ['name' => $name, 'path' => $target];
        }

        return $out !== [] ? $out : [['name' => 'Sheet1', 'path' => 'xl/worksheets/sheet1.xml']];
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
                // Formules : préférer la valeur calculée affichée (<v>) si présente.
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

    /**
     * @return list<array{r1:int,c1:int,r2:int,c2:int}>
     */
    private function parseMergeRanges(string $sheetXml): array
    {
        $sx = $this->loadXml($sheetXml);
        if ($sx === null || ! isset($sx->mergeCells->mergeCell)) {
            return [];
        }

        $ranges = [];
        foreach ($sx->mergeCells->mergeCell as $merge) {
            $ref = (string) ($merge['ref'] ?? '');
            if (! preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/i', $ref, $m)) {
                continue;
            }
            $ranges[] = [
                'c1' => $this->columnIndexFromLetters($m[1]),
                'r1' => ((int) $m[2]) - 1,
                'c2' => $this->columnIndexFromLetters($m[3]),
                'r2' => ((int) $m[4]) - 1,
            ];
        }

        return $ranges;
    }

    /**
     * Recopie la valeur top-left sur toute la zone fusionnée.
     *
     * @param  list<list<string>>  $grid
     * @param  list<array{r1:int,c1:int,r2:int,c2:int}>  $merges
     * @return list<list<string>>
     */
    private function applyMerges(array $grid, array $merges): array
    {
        foreach ($merges as $merge) {
            $value = $grid[$merge['r1']][$merge['c1']] ?? '';
            for ($r = $merge['r1']; $r <= $merge['r2']; $r++) {
                while (count($grid) <= $r) {
                    $grid[] = [];
                }
                for ($c = $merge['c1']; $c <= $merge['c2']; $c++) {
                    while (count($grid[$r]) <= $c) {
                        $grid[$r][] = '';
                    }
                    if ($r === $merge['r1'] && $c === $merge['c1']) {
                        continue;
                    }
                    if (trim((string) ($grid[$r][$c] ?? '')) === '') {
                        $grid[$r][$c] = $value;
                    }
                }
            }
        }

        // Ré-aligner largeurs.
        $maxCol = 0;
        foreach ($grid as $row) {
            $maxCol = max($maxCol, count($row) - 1);
        }
        $out = [];
        foreach ($grid as $row) {
            $line = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $line[] = (string) ($row[$c] ?? '');
            }
            $out[] = $line;
        }

        return $out;
    }

    private function columnIndexFromRef(string $ref): int
    {
        if (! preg_match('/^([A-Z]+)/i', $ref, $m)) {
            return 0;
        }

        return $this->columnIndexFromLetters($m[1]);
    }

    private function columnIndexFromLetters(string $letters): int
    {
        $letters = strtoupper($letters);
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

        // Formule avec valeur cachée / affichée.
        if (isset($cell->f) && $raw !== '') {
            return $raw;
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
        $xml = preg_replace('/\sxmlns(:\w+)?="[^"]*"/', '', $xml) ?? $xml;
        $xml = preg_replace('/(<\/?)(\w+):([^>\s\/]+)/', '$1$3', $xml) ?? $xml;
        // Attributs namespacés (ex. r:id → id).
        $xml = preg_replace('/\s\w+:(\w+)=/', ' $1=', $xml) ?? $xml;

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
