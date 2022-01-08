<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Contracts;

interface TokenizerInterface
{
    public const TOK_SPACE = ' ';
    public const TOK_COMMA = ',';
    public const TOK_PERIOD = '.';
    public const TOK_HYPHEN = '-';

    /**
     * @return string[]
     */
    public function tokenize(string $str): array;
}
