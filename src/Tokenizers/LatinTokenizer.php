<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Tokenizers;

use Bundsgaard\Phrasesearch\Contracts\TokenizerInterface;

class LatinTokenizer implements TokenizerInterface
{
    public function tokenize(string $str): array
    {
        $tokens = [];

        $string = trim(preg_replace('/[\s\t\n]+/u', ' ', $str));
        $string = str_replace([
            '!', '?', ';', ':', '#', '|', '…',
            '%', '&', '=', '^', '*', '<', '>',
            '{', '}', '[', ']', '(', ')', '–', '—',
            '"', '“', '”',
        ], '', $string);
        $next = 0;
        $maxbytes = strlen($string);

        $symbols = [
            self::TOK_HYPHEN,
            self::TOK_SPACE,
            self::TOK_SINGLE_QUOTE,
            self::TOK_COMMA,
            self::TOK_PERIOD,
            self::TOK_PLUS,
            self::TOK_SLASH,
        ];

        $token = '';
        $prevChar = '';
        while ($next < $maxbytes) {
            $char = $this->nextChar($string, $next);

            switch ($char) {
                case self::TOK_SPACE:
                    if ($token !== self::TOK_EMPTY) {
                        $tokens[] = $token;
                    }

                    $token = '';
                    break;

                case self::TOK_SLASH:
                    $token .= ' ';
                    break;

                case self::TOK_PLUS:
                case self::TOK_EN_DASH:
                case self::TOK_EM_DASH:
                    break;

                case self::TOK_SINGLE_QUOTE:
                    if ($prevChar === self::TOK_EMPTY || in_array($prevChar, $symbols)) {
                        break;
                    }

                    $nextChar = $this->nextChar($string, $next, true);
                    if (in_array($nextChar, $symbols)) {
                        break;
                    }

                    $token .= $char;

                    break;

                case self::TOK_COMMA:
                case self::TOK_PERIOD:
                    $nextChar = $this->nextChar($string, $next, true);
                    if (is_numeric($prevChar) && is_numeric($nextChar)) {
                        $token .= $char;
                    }

                    if (is_numeric($prevChar) && $nextChar == self::TOK_SPACE) {
                        $token .= $char;
                    }

                    break;

                case self::TOK_HYPHEN:
                    if ($prevChar === self::TOK_EMPTY || in_array($prevChar, $symbols)) {
                        break;
                    }

                    $nextChar = $this->nextChar($string, $next, true);
                    if ($nextChar === self::TOK_EMPTY || in_array($nextChar, $symbols)) {
                        break;
                    }

                    $token .= $char;

                    break;

                default:
                    $token .= $char;
                    break;
            }

            $prevChar = $char;
        }

        // Remember to add the last token if not empty
        if ($token !== self::TOK_EMPTY) {
            $tokens[] = $token;
        }

        return $tokens;
    }

    private function nextChar(string $string, int &$offset, bool $tmp = false): string
    {
        if ($tmp) {
            $next = $offset;
            return (string) grapheme_extract($string, 1, GRAPHEME_EXTR_COUNT, $offset, $next);
        }

        return (string) grapheme_extract($string, 1, GRAPHEME_EXTR_COUNT, $offset, $offset);
    }
}
