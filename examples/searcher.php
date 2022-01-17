<?php

declare(strict_types=1);

use Bundsgaard\Phrasesearch\{
    Analyzer,
    Entities\Document,
    Normalizers\Collator,
    Normalizers\Lowercaser,
    Scoring\TfIdf,
    Searcher,
    Support\EchoLogger,
    Support\ExecutionTimer,
    Tokenizers\LatinTokenizer
};

ini_set('memory_limit', '2048M');

require '../vendor/autoload.php';

$analyzer = new Analyzer(new LatinTokenizer());

$analyzer->addStopwords(require 'stopwords.php');

$analyzer->addNormalizer(new Collator(__DIR__ . '/../data/base.col'));
$analyzer->addNormalizer(new Lowercaser());
// $analyzer->addNormalizer(new Stemmer(new StemmerManager()));

$methodIdsToLanguage = require __DIR__ . '/../data/methods.php';
$supportedLanguages = array_unique(array_merge(...array_values($methodIdsToLanguage)));

$searcher = new Searcher(
    $analyzer,
    new EchoLogger()
);

$searcher->setDebugMode();

$searcher->load(
    __DIR__ . '/../data/index-da-2.dat',
    __DIR__ . '/../data/database-da-2.csv'
);

$executionTimer = new ExecutionTimer();
$executionTimer->start();

$searchResult = $searcher->search(
    $supportedLanguages,
    $argv[1] ?? ''
);

$searchResult->setScorer(new TfIdf($searchResult->getTotalNumberOfDocuments()));
$searchResult->rank();

$executionTimer->end();
print_r($executionTimer->__toString());

print_r(array_map(function (Document $document) {
    return $document->getContent() . ' | score=' . round($document->getScore(), 3);
}, $searchResult->get()));
