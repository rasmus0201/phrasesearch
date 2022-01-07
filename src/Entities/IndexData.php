<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Entities;

class IndexData
{
    private string $documentId;
    private string $externalId;
    private float $tfidf;

    public function __construct(
        string $documentId,
        string $externalId,
        float $tfidf
    ) {
        $this->documentId = $documentId;
        $this->externalId = $externalId;
        $this->tfidf = $tfidf;
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

    public function getTfidf(): float
    {
        return $this->tfidf;
    }

    public function setTfidf(float $tfidf): self
    {
        $this->tfidf = $tfidf;

        return $this;
    }
}
