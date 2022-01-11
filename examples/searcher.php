<?php

declare(strict_types=1);

ini_set('memory_limit', '2048M');

require '../vendor/autoload.php';

use Bundsgaard\Phrasesearch\Collator;
use Bundsgaard\Phrasesearch\Entities\Document;
use Bundsgaard\Phrasesearch\Searcher;
use Bundsgaard\Phrasesearch\Support\EchoLogger;
use LanguageDetection\Language;
use Wamania\Snowball\StemmerManager;

$searcher = new Searcher(
    new Collator(__DIR__ . '/../data/base.col'),
    new StemmerManager(),
    new EchoLogger()
);

$searcher->setDebugMode();

$searcher->load(__DIR__ . '/../data/documents.dat', __DIR__ . '/../data/index.dat');

$supportedLanguages = array_unique(array_merge(...array_values(require __DIR__ . '/../data/methods.php')));
$ld = new Language($supportedLanguages);

$results = $searcher->search(
    $ld,
    explode(',', $argv[2] ?? []),
    $argv[1] ?? ''
);

$resultHeadwords = array_map(
    function ($methodId) {
        return array_map(fn (Document $d) => $d->getExternalId() . ' = ' .   $d->getHeadword(), $methodId);
    },
    $results
);

print_r($resultHeadwords);
echo PHP_EOL;
