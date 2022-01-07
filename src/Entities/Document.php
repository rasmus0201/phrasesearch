<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Entities;

use Stringable;

class Document implements Stringable
{
    private string $documentId;
    private string $externalId;
    private string $headWord;
    private int $wordCount;
    private float $score;

    /** @var array<string, int> */
    private array $termFrequencies;

    public function __construct(
        string $documentId,
        string $externalId,
        string $headWord,
        int $wordCount,
        float $score,
        array $termFrequencies
    ) {
        $this->documentId = $documentId;
        $this->externalId = $externalId;
        $this->headWord = $headWord;
        $this->wordCount = $wordCount;
        $this->score = $score;
        $this->termFrequencies = $termFrequencies;
    }

    public function __toString(): string
    {
        return serialize([
            'documentId' => $this->documentId,
            'externalId' => $this->externalId,
            'headWord' => $this->headWord,
            'wordCount' => $this->wordCount,
            'score' => $this->score,
            'termFrequencies' => $this->termFrequencies,
        ]);
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function setDocumentId(string $documentId): self
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getMethodId(): string
    {
        return explode('$', $this->externalId)[0];
    }

    public function getLemmaId(): string
    {
        return explode('$', $this->externalId)[1];
    }

    public function getHeadWord(): string
    {
        return $this->headWord;
    }

    public function setHeadWord(string $headWord): self
    {
        $this->headWord = $headWord;

        return $this;
    }

    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    public function setWordCount(int $wordCount): self
    {
        $this->wordCount = $wordCount;

        return $this;
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

    public function getTermFrequencies(): array
    {
        return $this->termFrequencies;
    }

    /**
     * @param array<string, int> $termFrequencies
     */
    public function setTermFrequencies(array $termFrequencies): self
    {
        $this->termFrequencies = $termFrequencies;

        return $this;
    }
}
