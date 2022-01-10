<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Contracts;

interface NormalizerInterface
{
    public function normalize(string $str, ?string $language = null): string;
}
