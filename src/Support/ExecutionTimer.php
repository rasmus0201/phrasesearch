<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Support;

use Stringable;

class ExecutionTimer implements Stringable
{
    private array $startTime;
    private array $endTime;

    public function start(): void
    {
        $this->startTime = getrusage();
    }

    public function end(): void
    {
        $this->endTime = getrusage();
    }

    public function __toString()
    {
        return 'This process used ' . $this->runTime($this->endTime, $this->startTime, 'utime') .
            ' ms for its computations. It spent ' . $this->runTime($this->endTime, $this->startTime, 'stime') .
            ' ms in system calls';
    }

    private function runTime(array $ru, array $rus, string $index): float
    {
        return ($ru["ru_$index.tv_sec"] * 1000 + intval($ru["ru_$index.tv_usec"] / 1000))
            -  ($rus["ru_$index.tv_sec"] * 1000 + intval($rus["ru_$index.tv_usec"] / 1000));
    }
}
