<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use RuntimeException;

class Collator
{
    /** @var array<string, string> */
    private array $collations;

    /**
     * @throws RuntimeException
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new RuntimeException('Collation file did not exist at specified path!');
        }

        $collationHandle = fopen($path, 'r');
        if (!$collationHandle) {
            throw new RuntimeException('Could not open collation file!');
        }

        $collationMap = [];
        while (($line = fgets($collationHandle)) !== false) {
            [$from, $to] = explode('=', $line);
            $collationMap[$from] = $to;
        }
        fclose($collationHandle);

        $collationMap["\n"] = "";
        $collationMap["\r"] = "";

        $this->collations = $collationMap;
    }

    public function get()
    {
        return $this->collations;
    }
}
