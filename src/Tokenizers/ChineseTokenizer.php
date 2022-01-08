<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Tokenizers;

use Bundsgaard\Phrasesearch\Contracts\TokenizerInterface;
use Fukuball\Jieba\{Finalseg, Jieba};

class ChineseTokenizer implements TokenizerInterface
{
    public function tokenize(string $str): array
    {
        Jieba::init();
        Finalseg::init();

        return Jieba::cutForSearch($str);
    }
}
