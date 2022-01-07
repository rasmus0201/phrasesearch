<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Support;

use Psr\Log\AbstractLogger;

class EchoLogger extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        echo "[$level]: $message" . PHP_EOL;
    }
}
