<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use Bundsgaard\Phrasesearch\Contracts\{NormalizerInterface, TokenizerInterface};

class Analyzer
{
    private TokenizerInterface $tokenizer;

    /** @var NormalizerInterface[] */
    private array $normalizers = [];

    /** @var array<string, string[]> */
    private array $stopwords = [];

    public function __construct(
        TokenizerInterface $tokenizer
    ) {
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
    public function analyze(string $string, string $language, array $config = []): array
    {
        $config = array_merge([
            'ignored_normalizers' => [],
            'use_stop_words_doc_length' => 3
        ], $config);

        $tokens = $this->tokenizer->tokenize($string);
        $tokenCount = count($tokens);

        $terms = [];

        foreach ($tokens as $token) {
            if (
                $config['use_stop_words_doc_length'] > 0 &&
                $config['use_stop_words_doc_length'] >= $tokenCount &&
                isset($this->stopwords[$language]) &&
                in_array($token, $this->stopwords[$language])
            ) {
                continue;
            }

            $term = $token;
            foreach ($this->normalizers as $normalizer) {
                if (in_array(get_class($normalizer), $config['ignored_normalizers'])) {
                    continue;
                }

                $term = $normalizer->normalize($term, $language);
            }

            $terms[] = $term;
        }

        return $terms;
    }
}
