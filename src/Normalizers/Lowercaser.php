<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Normalizers;

use Bundsgaard\Phrasesearch\Contracts\NormalizerInterface;

class Lowercaser implements NormalizerInterface
{
    public function normalize(string $str, ?string $language = null): string
    {
        return mb_strtolower($str);
    }
}
