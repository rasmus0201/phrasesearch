<?php

declare(strict_types=1);

use Bundsgaard\Phrasesearch\{
    Analyzer,
    Normalizers\Collator,
    Normalizers\Lowercaser,
    Normalizers\Stemmer,
    Searcher2,
    Support\EchoLogger,
    Tokenizers\LatinTokenizer
};
use LanguageDetection\Language as LanguageDetector;
use Wamania\Snowball\StemmerManager;

ini_set('memory_limit', '2048M');

require '../vendor/autoload.php';

$analyzer = new Analyzer(new LatinTokenizer());

$analyzer->addNormalizer(new Collator(__DIR__ . '/../data/base.col'));
$analyzer->addNormalizer(new Lowercaser());
$analyzer->addNormalizer(new Stemmer(new StemmerManager()));

$supportedLanguages = array_unique(array_merge(...array_values(require 'methods.php')));
$ld = new LanguageDetector($supportedLanguages);

$searcher = new Searcher2(
    $analyzer,
    $ld,
    new EchoLogger()
);

$searcher->setDebugMode();

$searcher->load(__DIR__ . '/../data/index-da-2.dat');

$methodIdsToLanguage = require 'methods.php';

$results = $searcher->search(
    array_keys($methodIdsToLanguage),
    $argv[1] ?? ''
);

$count = count($results);
var_dump($count);
if ($count < 25) {
    var_dump($results);
}
