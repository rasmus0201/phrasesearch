<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use Bundsgaard\Phrasesearch\Contracts\{NormalizerInterface, TokenizerInterface};

class Analyzer
{
    private TokenizerInterface $tokenizer;
    private int $useStopwordsWhenDocLength;

    /** @var NormalizerInterface[] */
    private array $normalizers = [];

    /** @var array<string, string[]> */
    private array $stopwords = [];

    public function __construct(
        TokenizerInterface $tokenizer,
        int $useStopwordsWhenDocLength = 3
    ) {
        $this->useStopwordsWhenDocLength = $useStopwordsWhenDocLength;
        $this->tokenizer = $tokenizer;
    }

    public function addNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizers[] = $normalizer;
    }

    /**
     * @param array<string, string[]> $stopwords Language-keyed stopwords list
     */
    public function addStopwords(array $stopwords): void
    {
        $this->stopwords = $stopwords;
    }

    /**
     * @return string[]
     */
    public function analyze(string $string, string $language): array
    {
        $tokens = $this->tokenizer->tokenize($string);
        $tokenCount = count($tokens);

        $terms = [];

        foreach ($tokens as $token) {
            if (
                $this->useStopwordsWhenDocLength > 0 &&
                $this->useStopwordsWhenDocLength >= $tokenCount &&
                isset($this->stopwords[$language]) &&
                in_array($token, $this->stopwords[$language])
            ) {
                continue;
            }

            $term = $token;
            foreach ($this->normalizers as $normalizer) {
                $term = $normalizer->normalize($term, $language);
            }

            $terms[] = $term;
        }

        return $terms;
    }
}
