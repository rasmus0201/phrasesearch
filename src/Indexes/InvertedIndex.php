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

    public function addDocuments(string $key, array $docIds): void
    {
        // TODO: Optimize the documents list with a skip-pointer list
        // @see https://nlp.stanford.edu/IR-book/html/htmledition/faster-postings-list-intersection-via-skip-pointers-1.html
        $this->data[$key] = [
            'freq' => count($docIds),
            'documents' => $docIds,
        ];
    }

    public function addDocument(string $key, int $docId): void
    {
        if (!$this->has($key)) {
            $this->data[$key] = [
                'freq' => 0,
                'documents' => [],
            ];
        }

        $this->data[$key]['freq'] += 1;
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

    public function sort(): self
    {
        ksort($this->data, SORT_FLAG_CASE | SORT_NATURAL);

        return $this;
    }

    public function store()
    {
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
        sort($row['documents'], SORT_NUMERIC);

        $termFreq = $row['freq'];
        $postings = implode('|', $row['documents']);

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
            $this->addDocuments($row[0], array_map(
                fn ($id) => intval($id),
                explode('|', $row[2])
            ));
        }
    }
}
