<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Normalizers;

use Bundsgaard\Phrasesearch\Contracts\NormalizerInterface;
use Wamania\Snowball\StemmerManager;

class Stemmer implements NormalizerInterface
{
    private StemmerManager $stemmerManager;

    public function __construct(StemmerManager $stemmerManager)
    {
        $this->stemmerManager = $stemmerManager;
    }

    public function normalize(string $str, ?string $language = null): string
    {
        return $this->stemmerManager->stem($str, $language);
    }
}
