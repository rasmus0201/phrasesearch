<?php

declare(strict_types=1);

use Bundsgaard\Phrasesearch\{
    Analyzer,
    Indexer2,
    Normalizers\Collator,
    Normalizers\Lowercaser,
    Normalizers\Stemmer,
    Support\EchoLogger,
    Tokenizers\LatinTokenizer
};
use Symfony\Component\Console\Output\ConsoleOutput;
use Wamania\Snowball\StemmerManager;

ini_set('memory_limit', '2048M');

require '../vendor/autoload.php';

$analyzer = new Analyzer(new LatinTokenizer());

$analyzer->addStopwords(require 'stopwords.php');

$analyzer->addNormalizer(new Collator(__DIR__ . '/../data/base.col'));
$analyzer->addNormalizer(new Lowercaser());
$analyzer->addNormalizer(new Stemmer(new StemmerManager()));

$indexer = new Indexer2(
    $analyzer,
    new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG),
    new EchoLogger()
);

$indexer->setDebugMode();

$methodIdsToLanguage = require 'methods.php';
$indexer->create(
    __DIR__ . '/../data/database-da-2.csv',
    __DIR__ . '/../data/index-da-3.dat',
    $methodIdsToLanguage
);
