<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Indexes;

use Countable;
use RuntimeException;

class InvertedIndex implements Countable
{
    /**
     * Should be used as a key-value dictionary.
     *
     * @var array<string, array<string, array<string, int|int[]>>>
     */
    private array $data = [];

    /** @var resource */
    private $fh;

    private string $path;
    private int $count = 0;

    public function __construct(string $path, array $languages = [])
    {
        $this->path = $path;

        foreach ($languages as $language) {
            $this->data[$language] = [];
        }

        $this->loadIndex($path);
    }

    public function addDocument(string $language, string $key, int $docId, int $freq = 1): void
    {
        if (!$this->has($language, $key)) {
            if (!isset($this->data[$language])) {
                $this->data[$language] = [];
            }

            $this->data[$language][$key] = [
                'freq' => $freq,
                'documents' => [$docId],
            ];

            $this->count += 1;

            return;
        }

        $this->count += 1;
        $this->data[$language][$key]['freq'] += $freq;
        $this->data[$language][$key]['documents'][] = $docId;
    }

    public function has(string $language, string $key): bool
    {
        return isset($this->data[$language][$key]);
    }

    public function get(string $language, string $key, $default = null): ?array
    {
        return $this->data[$language][$key] ?? $default;
    }

    public function df(string $key): int
    {
        $freq = 0;

        foreach ($this->data as $index) {
            if (!isset($index[$key])) {
                continue;
            }

            $freq += $index[$key]['freq'];
        }

        return $freq;
    }

    public function delete(string $language, string $key): void
    {
        unset($this->data[$language][$key]);
    }

    public function count(): int
    {
        return $this->count;
    }

    public function store()
    {
        ksort($this->data, SORT_FLAG_CASE | SORT_NATURAL);

        $tmpFh = tmpfile();
        $path = stream_get_meta_data($tmpFh)['uri'];

        $batch = 0;
        $content = '';
        foreach ($this->data as $language => $index) {
            foreach ($index as $key => $row) {
                if ($batch === 50_000) {
                    fwrite($tmpFh, $content);
                    $content = '';
                    $batch = 0;
                }

                sort($row['documents'], SORT_NUMERIC);
                $content .= $this->formatRow($language, (string) $key, $row) . "\n";
                $batch += 1;
            }
        }

        // Write final content without final newline
        fwrite($tmpFh, trim($content));
        rename($path, $this->path);
        fclose($tmpFh);

        $this->writeMetadata();
    }

    private function formatRow(string $language, string $key, array $row): string
    {
        $termFreq = $row['freq'];
        $postings = implode(
            '|',
            $this->compressPostings($row['documents'])
        );

        return "\"{$language}|{$key}\",{$termFreq},\"{$postings}\"";
    }

    private function writeMetadata()
    {
        if (!$handle = fopen($this->path . '.metadata', 'w')) {
            throw new RuntimeException('Could open file for metadata');
        }

        fwrite($handle, "\"total_docs\",{$this->totalNumberOfDocuments}\n");
        fclose($handle);
    }

    private function loadIndex(string $path): void
    {
        if (!file_exists($path)) {
            $this->fh = fopen($path, 'w+');

            return;
        }

        $this->fh = fopen($path, 'a+');
        fseek($this->fh, 0);

        while ($row = fgetcsv($this->fh)) {
            list($language, $key) = explode('|', $row[0]);

            $this->loadPostings($language, $key, array_map(
                fn ($id) => intval($id),
                explode('|', $row[2])
            ));
        }

        $this->loadMetadata($path . '.metadata');
    }

    private function loadPostings(string $language, string $key, array $docIds): void
    {
        if (!isset($this->data[$language])) {
            $this->data[$language] = [];
        }

        $this->data[$language][$key] = [
            'freq' => count($docIds),
            'documents' => $this->decompressPostings($docIds),
        ];
    }

    private function loadMetadata(string $path)
    {
        if (!file_exists($path)) {
            return;
        }

        $handle = fopen($path, 'r');
        while ($row = fgetcsv($handle)) {
            switch ($row[0]) {
                case 'total_docs':
                    $this->count = (int) $row[1];
                    break;

                default:
                    break;
            }
        }

        fclose($handle);
    }

    private function compressPostings(array $postings)
    {
        $initialId = $postings[0];
        $out = [$initialId];

        for ($i = 1; $i < count($postings) - 1; $i++) {
            $newId = $postings[$i] - $initialId;
            $out[] = $newId;
            $initialId += $newId;
        }

        return $out;
    }

    private function decompressPostings(array $postings)
    {
        $initialId = $postings[0];
        $out = [$initialId];

        for ($i = 1; $i < count($postings) - 1; $i++) {
            $idGap = $postings[$i];
            $out[] = $initialId + $idGap;
            $initialId += $idGap;
        }

        return $out;
    }
}
