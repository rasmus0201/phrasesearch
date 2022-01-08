<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Tokenizers;

use Bundsgaard\Phrasesearch\Contracts\TokenizerInterface;

class LatinTokenizer implements TokenizerInterface
{
    public function tokenize(string $str): array
    {
        $tokens = [];

        $string = trim(preg_replace('/[\s\t\n]+/', ' ', $str));
        $string = preg_replace('/[\!\?;:\#\{\}\(\)\[\]\"\|]/', '', $string);
        $next = 0;
        $maxbytes = strlen($string);

        $token = '';
        $prevChar = '';
        while ($next < $maxbytes) {
            $char = $this->nextChar($string, $next);

            switch ($char) {
                case self::TOK_SPACE:
                    if ($token !== '') {
                        $tokens[] = $token;
                    }

                    $token = '';
                    break;

                case self::TOK_COMMA:
                case self::TOK_PERIOD:
                    $nextChar = $this->nextChar($string, $next, true);
                    if (is_numeric($prevChar) && is_numeric($nextChar)) {
                        $token .= $char;
                    }
                    break;

                case self::TOK_HYPHEN:
                    if ($prevChar == '' || $prevChar == self::TOK_HYPHEN || $prevChar == self::TOK_SPACE) {
                        break;
                    }

                    $nextChar = $this->nextChar($string, $next, true);
                    if (!empty($nextChar)) {
                        $token .= $char;
                    }
                    break;

                default:
                    $token .= $char;
                    break;
            }

            $prevChar = $char;
        }

        // Remember to add the last token
        $tokens[] = $token;

        return array_filter($tokens);
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
