<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Entities;

use Bundsgaard\Phrasesearch\Contracts\ScorerInterface;

class SearchResult
{
    private array $tokens;
    /** @var Document[] */
    private array $documents;
    private int $totalNumberOfDocuments;
    private ?ScorerInterface $scorer = null;

    /**
     * @param string[] $tokens
     * @param Document[] $documents
     * @param int $totalNumberOfDocuments
     */
    public function __construct(
        array $tokens,
        array $documents,
        int $totalNumberOfDocuments
    ) {
        $this->tokens = $tokens;
        $this->documents = $documents;
        $this->totalNumberOfDocuments = $totalNumberOfDocuments;
    }

    public function setScorer(ScorerInterface $scorer): void
    {
        $this->scorer = $scorer;
    }

    public function getTotalNumberOfDocuments(): int
    {
        return $this->totalNumberOfDocuments;
    }

    public function rank(): self
    {
        if (!$this->scorer) {
            return $this;
        }

        foreach ($this->documents as $document) {
            $this->scorer->score($this->tokens, $document);
        }

        usort($this->documents, function (Document $a, Document $b) {
            return $b->getScore() <=> $a->getScore();
        });

        return $this;
    }

    public function best(): ?Document
    {
        return $this->documents[0] ?? null;
    }

    public function get(): array
    {
        return $this->documents;
    }
}
