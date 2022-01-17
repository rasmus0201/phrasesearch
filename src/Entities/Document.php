<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Entities;

use Bundsgaard\Phrasesearch\Analyzer;
use Bundsgaard\Phrasesearch\Indexes\InvertedIndex;
use Bundsgaard\Phrasesearch\Normalizers\Stemmer;

class Document
{
    private int $id = 0;
    private string $methodId = '';
    private string $externalId = '';
    private string $language = '';
    private array $tokens = [];
    private int $tokenCount = 0;
    private float $score = 0.0;

    /** @var array<string, int> */
    private array $termFrequencies = [];

    /** @var array<string, int> */
    private array $relativeTermFrequencies = [];

    public function __construct(
        Analyzer $analyzer,
        InvertedIndex $invertedIndex,
        int $id,
        string $language,
        string $csv
    ) {
        $csvData = str_getcsv($csv);

        $this->id = $id;
        $this->language = $language;
        $this->methodId = $csvData[0];
        $this->externalId = $csvData[3];
        $this->tokens = $analyzer->analyze($csvData[1], $language, [
            'ignored_normalizers' => [Stemmer::class],
            'use_stop_words_doc_length' => 0
        ]);
        $this->tokenCount = count($this->tokens);

        foreach (array_unique($this->tokens) as $token) {
            $this->termFrequencies[$token] = $invertedIndex->df($token);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMethodId(): string
    {
        return $this->methodId;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getContent(): string
    {
        return implode(' ', $this->tokens);
    }

    public function getTermCount(): int
    {
        return $this->tokenCount;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function addScore(float $score): self
    {
        $this->score += $score;

        return $this;
    }

    /**
     * @return array<string, int>
     */
    public function getFrequencies()
    {
        return $this->termFrequencies;
    }

    /**
     * @return array<string, int>
     */
    public function getRelativeTermFrequencies(): array
    {
        if (!empty($this->relativeTermFrequencies)) {
            return $this->relativeTermFrequencies;
        }

        foreach ($this->tokens as $word) {
            if (!isset($this->relativeTermFrequencies[$word])) {
                $this->relativeTermFrequencies[$word] = 1;
            } else {
                $this->relativeTermFrequencies[$word] += 1;
            }
        }

        return $this->relativeTermFrequencies;
    }
}
