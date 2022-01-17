<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Scoring;

use Bundsgaard\Phrasesearch\Contracts\ScorerInterface;
use Bundsgaard\Phrasesearch\Entities\Document;

class TfIdf implements ScorerInterface
{
    private int $totalNumberOfDocuments;

    public function __construct(int $totalNumberOfDocuments)
    {
        $this->totalNumberOfDocuments = $totalNumberOfDocuments;
    }

    public function score(array $terms, Document $document): float
    {
        $scores = [];

        $dfs = $document->getFrequencies();
        $tf = $document->getRelativeTermFrequencies();
        foreach ($document->getTokens() as $term) {
            $scores[$term] = $tf[$term] * log($this->totalNumberOfDocuments / $dfs[$term]);
        }

        $score = 0.0;
        foreach ($terms as $term) {
            $score += $scores[$term];
        }

        $score -= levenshtein(implode(' ', $terms), $document->getContent(), 10, 8, 10);

        $document->setScore(max($score, 0));

        return $score;
    }
}
