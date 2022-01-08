<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Entities;

use Wamania\Snowball\StemmerManager;

class Document
{
    private int $id = 0;
    private string $externalId = '';
    private string $headword = '';
    private int $termCount = 0;
    private float $score = 0.0;

    /** @var array<string, int> */
    private array $termFrequencies = [];

    public function __construct(
        StemmerManager $stemmer,
        int $id,
        string $externalId,
        string $language,
        string $headword
    ) {
        $this->id = $id;
        $this->headword = $headword;
        $this->externalId = $externalId;

        $terms = explode(' ', $headword);
        $this->termCount = count($terms);
        foreach ($terms as $word) {
            $stemmedWord = $stemmer->stem($word, $language);

            if (!isset($this->termFrequencies[$stemmedWord])) {
                $this->termFrequencies[$stemmedWord] = 1;
            } else {
                $this->termFrequencies[$stemmedWord] += 1;
            }
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getHeadword(): string
    {
        return $this->headword;
    }

    public function getTermCount(): int
    {
        return $this->termCount;
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

    /**
     * @return array<string, int>
     */
    public function getTermFrequencies(): array
    {
        return $this->termFrequencies;
    }
}
