<?php

declare(strict_types=1);

ini_set('memory_limit', '1024M');

require 'vendor/autoload.php';
require 'ExecutionTimer.php';
require 'collator.php';
require 'logger.php';

use Wamania\Snowball\StemmerManager;
use LanguageDetection\Language;

$documentsHandle = fopen('documents.dat', 'r');
$indexHandle = fopen('index.dat', 'r');
$inverseIndex = unserialize(fgets($indexHandle));

$entries = [];
while (($line = fgets($documentsHandle)) !== false) {
    $entry = unserialize($line);
    $dId = $entry['dId'];
    $mId = $entry['mId'];

    if (!isset($entries[$mId])) {
        $entries[$mId] = [];
    }

    $entries[$mId][$dId] = $entry;
}

fclose($documentsHandle);
fclose($indexHandle);

$collationMap = getCollation('base.col');
$collationKeys = array_keys($collationMap);
$collationValues = array_values($collationMap);

$stemmer = new StemmerManager();

$searchQuery = $argv[1] ?? "";
if (empty(trim($searchQuery))) {
    logger('Need to have a search query');
    exit(0);
}

$searchMethodIds = $argv[2] ?? "ordbogen-daen";
$searchMethodIds = explode(',', $searchMethodIds);

$executionTimer = new ExecutionTimer();
$executionTimer->start();

$availableMethods = require 'dictionaries.php';
$ld = new Language(array_unique(array_values($availableMethods)));

$guessedLanguages = $ld->detect($searchQuery)->bestResults()->close();
$language = key($guessedLanguages);

$searchQuery = str_replace($collationKeys, $collationValues, $searchQuery);
$searchQueryWords = explode(' ', $searchQuery);

$normalizedQueryWords = [];
foreach ($searchQueryWords as $word) {
    $normalizedQueryWords[] = $stemmer->stem($word, $language);
}
$normalizedQueryWords = array_unique($normalizedQueryWords);

/**
 * Find words in the index, group them by method, and take the highest ranking
 * word in the result, to use in a best ranking scenario. This will only use the unique words,
 * of the search query.
 */
$indexedResultsByWord = [];
$indexedResultsByMethod = [];
foreach ($normalizedQueryWords as $word) {
    $indexedResults = $inverseIndex[$word] ?? null;
    if (!$indexedResults) {
        continue;
    }

    $indexedResultsByWord[$word] = $indexedResults;
    foreach ($indexedResults as $result) {
        $methodId = $result['mId'];
        if (!isset($indexedResultsByMethod[$methodId])) {
            $indexedResultsByMethod[$methodId] = [];
        }

        if (!isset($indexedResultsByMethod[$methodId][$word])) {
            $indexedResultsByMethod[$methodId][$word] = ['bestScore' => 0, 'results' => []];
        }

        $indexedResultsByMethod[$methodId][$word]['results'][] = $result;
    }
}

foreach ($indexedResultsByMethod as $methodId => &$resultsByWord) {
    foreach ($resultsByWord as $word => &$results) {
        usort($results['results'], function ($doc1, $doc2) {
            return $doc2['tfidf'] <=> $doc1['tfidf'];
        });

        $results['bestScore'] = $results['results'][0]['tfidf'];
    }

    uasort($resultsByWord, function ($doc1, $doc2) {
        return $doc2['bestScore'] <=> $doc1['bestScore'];
    });
}

$intersectionResultsByMethod = [];
foreach ($indexedResultsByMethod as $methodId => $resultsByWord) {
    if (!isset($intersectionResultsByMethod[$methodId])) {
        $intersectionResultsByMethod[$methodId] = [];
    }

    $intersectionResults = [];
    $resultsById = [];
    foreach ($resultsByWord as $word => $results) {
        $ids = [];

        foreach ($results['results'] as $result) {
            $resultsById[$result['dId']] = $result;
            $ids[] = $result['dId'];
        }

        if (count($intersectionResults) === 0) {
            $intersectionResults = $ids;
            continue;
        }

        $intersectedResults = array_intersect(
            $intersectionResults,
            $ids
        );

        if (count($intersectedResults) > 0) {
            $intersectionResults = $intersectedResults;
        }
    }

    if (count($intersectionResults) > 0) {
        $intersectionResults = array_values($intersectionResults);
        $restoredIntersectionResults = [];
        foreach ($intersectionResults as $id) {
            $restoredIntersectionResults[] = $resultsById[$id];
        }
        $intersectionResults = $restoredIntersectionResults;

        uasort($intersectionResults, function ($doc1, $doc2) {
            return $doc2['tfidf'] <=> $doc1['tfidf'];
        });

        $intersectionResultsByMethod[$methodId] = $intersectionResults;
    } else {
        $intersectionResultsByMethod[$methodId] = [];
    }
}

$entriesByMethod = [];
foreach ($searchMethodIds as $searchMethodId) {
    $entriesByMethod[$searchMethodId] = [];

    if (!isset($intersectionResultsByMethod[$searchMethodId])) {
        continue;
    }

    $ids = array_column($intersectionResultsByMethod[$searchMethodId], 'dId');
    $bestScore = 0;
    foreach ($ids as $id) {
        if (isset($entries[$searchMethodId][$id])) {
            $entry = $entries[$searchMethodId][$id];
            $entriesByMethod[$searchMethodId][] = $entry;
            $bestScore = max($bestScore, $entry['s']);
        }
    }

    $entriesByMethod[$searchMethodId] = array_filter(
        $entriesByMethod[$searchMethodId],
        fn ($r) => $r['s'] >= ($bestScore / 2)
    );

    uasort($entriesByMethod[$searchMethodId], function ($doc1, $doc2) {
        return $doc2['s'] <=> $doc1['s'];
    });
}

$executionTimer->end();

$resultHeadwords = array_map(
    function ($m) {
        return array_map(fn ($e) => $e['hw'], $m);
    },
    $entriesByMethod
);
print_r($resultHeadwords);
logger("Guessed search languages: {$language}");
logger("Stemmed search query: " . implode(' ', $normalizedQueryWords));
logger((string) $executionTimer);
