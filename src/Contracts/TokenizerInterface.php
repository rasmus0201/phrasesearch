<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Contracts;

interface TokenizerInterface
{
    public const TOK_EMPTY = '';
    public const TOK_SPACE = ' ';
    public const TOK_COMMA = ',';
    public const TOK_PERIOD = '.';
    public const TOK_HYPHEN = '-';
    public const TOK_PLUS = '+';
    public const TOK_SLASH = '/';
    public const TOK_SINGLE_QUOTE = '\'';
    public const TOK_EN_DASH = '–';
    public const TOK_EM_DASH = '—';
    public const TOK_MINUS = '−';

    /**
     * @return string[]
     */
    public function tokenize(string $str): array;
}
