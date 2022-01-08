<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Support;

use Stringable;

class MemoryUsage implements Stringable
{
    private array $startMemory;
    private array $endMemory;

    public function start(): void
    {
        $this->startMemory = [
            'used' => memory_get_usage(),
            'allocated' => memory_get_usage(true),
            'peak_used' => memory_get_peak_usage(),
            'peak_allocated' => memory_get_peak_usage(true),
        ];
    }

    public function end(): void
    {
        $this->endMemory = [
            'used' => memory_get_usage(),
            'allocated' => memory_get_usage(true),
            'peak_used' => memory_get_peak_usage(),
            'peak_allocated' => memory_get_peak_usage(true),
        ];
    }

    public function __toString()
    {
        return 'This process used ' . $this->convert('used') .
            ' of memory (allocated: ' . $this->convert('allocated') . '). ' .
            'Peak load was ' . $this->convert('peak_used') . '. ' .
            '(allocated: ' . $this->convert('peak_allocated') . ')';
    }

    private function convert(string $index): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        $size = $this->memoryUsage($index);

        if ($size <= 0) {
            return '0b';
        }

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
    }

    private function memoryUsage(string $index): float
    {
        return ($this->endMemory[$index] - $this->startMemory[$index]);
    }
}
