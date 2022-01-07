<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Scoring;

use Bundsgaard\Phrasesearch\Contracts\ScorerInterface;
use Bundsgaard\Phrasesearch\Entities\Document;

class TfIdf implements ScorerInterface
{
    public function score(Document $document): float
    {
        return 0.0;
    }
}
