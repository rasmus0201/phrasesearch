<?php

declare(strict_types=1);

ini_set('memory_limit', '1024M');

require 'vendor/autoload.php';
require 'collator.php';
require 'logger.php';

use Wamania\Snowball\StemmerManager;

logger('... Preparing collation');
$collationMap = getCollation('base.col');
$collationKeys = array_keys($collationMap);
$collationValues = array_values($collationMap);
logger('Done Preparing collation');

$totalNumberOfDocuments = 0;
$totalTermFrequencies = [];
$documents = [];

$stemmer = new StemmerManager();

$availableMethods = require 'dictionaries.php';

logger('... Iterating database');
$handle = fopen('database.csv', 'r');
while ($row = fgetcsv($handle)) {
    $methodId = $row[0];
    if (!isset($availableMethods[$methodId])) {
        continue;
    }

    $language = $availableMethods[$methodId];
    $headword = $row[1];
    $lemmaId = $row[3];

    $headword = str_replace($collationKeys, $collationValues, $headword);
    $stemmedHeadword = [];

    $termFrequencies = [];
    $words = explode(' ', $headword);
    foreach ($words as $word) {
        $stemmedWord = $stemmer->stem($word, $language);
        $stemmedHeadword[] = $stemmedWord;

        if (!isset($termFrequencies[$stemmedWord])) {
            $termFrequencies[$stemmedWord] = 1;
        } else {
            $termFrequencies[$stemmedWord] += 1;
        }

        if (!isset($totalTermFrequencies[$stemmedWord])) {
            $totalTermFrequencies[$stemmedWord] = 1;
        } else {
            $totalTermFrequencies[$stemmedWord] += 1;
        }
    }

    $docId = md5($methodId . $lemmaId . $row[1]);

    $documents[$docId] = [
        'dId' => $docId,
        'lId' => $lemmaId,
        'mId' => $methodId,
        'wc' => count($words),
        'hw' => $row[1],
        's' => 0,
        'tf' => $termFrequencies
    ];
    $totalNumberOfDocuments++;
}
fclose($handle);
logger('Done iterating database');

logger('... Creating inverse index');
$documentsHandle = fopen('documents.dat', 'w+');
$indexHandle = fopen('index.dat', 'w+');
$inverseIndex = [];
foreach ($documents as $docId => $document) {
    $methodId = $document['mId'];
    $wordCount = $document['wc'];

    $score = $document['s'];
    foreach ($document['tf'] as $word => $frequency) {
        $tf = round($frequency / $wordCount, 6);
        $idf = round(log($totalNumberOfDocuments / $totalTermFrequencies[$word]), 6);

        $indexData = [
            'dId' => $docId,
            'lId' => $lemmaId,
            'mId' => $methodId,
            'tfidf' => round($tf * $idf, 6),
        ];
        $score += $indexData['tfidf'];

        if (!isset($inverseIndex[$word])) {
            $inverseIndex[$word] = [$indexData];
        } else {
            $inverseIndex[$word][] = $indexData;
        }
    }

    $document['s'] = $score;
    fwrite($documentsHandle, serialize($document) . "\n");
}
logger('... Serializing inverse index');
fwrite($indexHandle, serialize($inverseIndex));
fclose($indexHandle);
fclose($documentsHandle);
logger('Done with inverse index');
