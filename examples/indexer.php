<?php

declare(strict_types=1);
ini_set('memory_limit', '2048M');

require '../vendor/autoload.php';

use Bundsgaard\Phrasesearch\Collator;
use Bundsgaard\Phrasesearch\Indexer;
use Bundsgaard\Phrasesearch\Support\EchoLogger;
use Wamania\Snowball\StemmerManager;

$indexer = new Indexer(
    new Collator(__DIR__ . '/../data/base.col'),
    new StemmerManager(),
    new EchoLogger()
);

$indexer->setDebugMode();

$methodIdsToLanguage = require 'methods.php';
$indexer->loadDocuments(__DIR__ . '/../data/database-da.csv', $methodIdsToLanguage);
$indexer->save(
    __DIR__ . '/../data/documents.dat',
    __DIR__ . '/../data/index.dat'
);
