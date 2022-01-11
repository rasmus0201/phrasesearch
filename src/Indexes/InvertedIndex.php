<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Indexes;

use Countable;

class InvertedIndex implements Countable
{
    /**
     * Should be used as a key-value dictionary.
     *
     * @var array<string, array<string, int|int[]>>
     */
    private array $data = [];

    /** @var resource */
    private $fh;

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->loadIndex($path);
    }

    /**
     * @param array<string, int[]> $documents Keys be the terms and value is the postings list
     */
    public function addDocuments(array $documents): void
    {
        // TODO: Optimize the documents list with a skip-pointer list or other method for faster search
        // @see https://nlp.stanford.edu/IR-book/html/htmledition/faster-postings-list-intersection-via-skip-pointers-1.html
        foreach ($documents as $key => $postings) {
            $freq = count($postings);

            if (!$this->has($key)) {
                $this->data[$key] = [
                    'freq' => $freq,
                    'documents' => $postings,
                ];

                continue;
            }

            $this->data[$key]['freq'] += $freq;
            $this->data[$key]['documents'] = array_merge($this->data[$key]['documents'], $postings);
        }
    }

    public function addDocument(string $key, int $docId, int $freq = 1): void
    {
        if (!$this->has($key)) {
            $this->data[$key] = [
                'freq' => $freq,
                'documents' => [$docId],
            ];

            return;
        }

        $this->data[$key]['freq'] += $freq;
        $this->data[$key]['documents'][] = $docId;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function get(string $key, $default = null): ?array
    {
        return $this->data[$key] ?? $default;
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function store()
    {
        ksort($this->data, SORT_FLAG_CASE | SORT_NATURAL);

        $tmpFh = tmpfile();
        $path = stream_get_meta_data($tmpFh)['uri'];

        $batch = 0;
        $content = '';
        foreach ($this->data as $key => $row) {
            if ($batch === 50_000) {
                fwrite($tmpFh, $content);
                $content = '';
                $batch = 0;
            }

            sort($row['documents'], SORT_NUMERIC);
            $content .= $this->formatRow($key, $row) . "\n";
            $batch += 1;
        }

        // Write final content without final newline
        fwrite($tmpFh, trim($content));
        rename($path, $this->path);
        fclose($tmpFh);
    }

    private function formatRow($key, array $row): string
    {
        $termFreq = $row['freq'];
        $postings = implode(
            '|',
            $this->compressPostings($row['documents'])
        );

        return "\"{$key}\",{$termFreq},\"{$postings}\"";
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
            $this->loadPostings($row[0], array_map(
                fn ($id) => intval($id),
                explode('|', $row[2])
            ));
        }
    }

    private function loadPostings(string $key, array $docIds): void
    {
        $this->data[$key] = [
            'freq' => count($docIds),
            'documents' => $this->decompressPostings($docIds),
        ];
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
