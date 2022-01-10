<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Normalizers;

use Bundsgaard\Phrasesearch\Contracts\NormalizerInterface;
use RuntimeException;

class Collator implements NormalizerInterface
{
    /** @var string[] */
    private array $collationKeys = [];

    /** @var string[] */
    private array $collationValues = [];

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

        $collationMap["\n"] = '';
        $collationMap["\r"] = '';

        $this->collationKeys = array_keys($collationMap);
        $this->collationValues = array_values($collationMap);
    }

    public function normalize(string $str, ?string $language = null): string
    {
        return str_replace(
            $this->collationKeys,
            $this->collationValues,
            $str
        );
    }
}
