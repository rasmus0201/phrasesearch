<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Contracts;

use Bundsgaard\Phrasesearch\Entities\Document;

interface ScorerInterface
{
    public function score(Document $document): float;
}
