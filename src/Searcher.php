<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use Bundsgaard\Phrasesearch\Entities\{Document, IndexData};
use Bundsgaard\Phrasesearch\Support\ExecutionTimer;
use LanguageDetection\Language;
use Psr\Log\{LoggerInterface, NullLogger};
use RuntimeException;
use Wamania\Snowball\StemmerManager;

class Searcher
{
    private Collator $collator;
    private StemmerManager $stemmerManager;
    private ?LoggerInterface $logger = null;
    private bool $debugging = false;

    /** @var array<string, string> */
    private array $collationMap = [];

    /** @var string[] */
    private array $collationKeys = [];

    /** @var string[] */
    private array $collationValues = [];

    /** @var array<string, IndexData[]> */
    private array $inverseIndex = [];

    /** @var array<string, Document> */
    private array $documents = [];

    public function __construct(
        Collator $collator,
        StemmerManager $stemmerManager,
        ?LoggerInterface $logger = null
    ) {
        $this->collator = $collator;
        $this->stemmerManager = $stemmerManager;
        $this->setLogger($logger);

        $this->loadCollation();
    }

    public function setLogger(?LoggerInterface $logger = null): void
    {
        if ($logger === null) {
            $logger = new NullLogger();
        }

        $this->logger = $logger;
    }

    public function setDebugMode(bool $value = true): void
    {
        $this->debugging = $value;
    }

    public function load(string $documentsDataPath, string $indexDataPath): void
    {
        if (!$documentsHandle = fopen($documentsDataPath, 'r')) {
            throw new RuntimeException('Could not load documents data');
        }

        if (!$indexHandle = fopen($indexDataPath, 'r')) {
            throw new RuntimeException('Could not load inde data');
        }

        $this->log('... Loading inverse index');
        $this->inverseIndex = unserialize(fgets($indexHandle));
        $this->log('... Done loading inverse index');

        $this->log('... Loading documents');
        while (($line = fgets($documentsHandle)) !== false) {
            /** @var Document */
            $document = unserialize($line);

            $this->documents[$document->getDocumentId()] = $document;
        }
        $this->log('... Done loading documents');

        fclose($documentsHandle);
        fclose($indexHandle);
    }

    public function search(Language $languageDetector, array $methodIds, string $query)
    {
        if (empty($methodIds)) {
            throw new RuntimeException('Exepected non-empty method id');
        }

        $query = trim($query);
        if (empty($query)) {
            throw new RuntimeException('Exepected non-empty search query');
        }

        $executionTimer = new ExecutionTimer();
        $executionTimer->start();

        $guessedLanguages = $languageDetector->detect($query)->bestResults()->close();
        $language = key($guessedLanguages);

        $searchQuery = str_replace($this->collationKeys, $this->collationValues, $query);
        $searchQueryWords = explode(' ', $searchQuery);

        $normalizedQueryWords = [];
        foreach ($searchQueryWords as $word) {
            $normalizedQueryWords[] = $this->stemmerManager->stem($word, $language);
        }
        $normalizedQueryWords = array_unique($normalizedQueryWords);

        /**
         * Find words in the index, group them by method, and take the highest ranking
         * word in the result, to use in a best ranking scenario. This will only use the unique words,
         * of the search query.
         */

        /** @var array<string, array<string, mixed>> */
        $indexedResultsByMethod = [];
        foreach ($normalizedQueryWords as $word) {
            /** @var string[]|null */
            $indexedResults = $this->inverseIndex[$word] ?? null;
            if (!$indexedResults) {
                continue;
            }

            foreach ($indexedResults as $indexDataRaw) {
                $indexData = $this->createIndexData($indexDataRaw);

                $methodId = $indexData->getMethodId();
                if (!isset($indexedResultsByMethod[$methodId])) {
                    $indexedResultsByMethod[$methodId] = [];
                }

                if (!isset($indexedResultsByMethod[$methodId][$word])) {
                    $indexedResultsByMethod[$methodId][$word] = ['bestScore' => 0, 'results' => []];
                }

                $indexedResultsByMethod[$methodId][$word]['results'][] = $indexData;
            }
        }

        // foreach ($indexedResultsByMethod as $methodId => &$resultsByWord) {
        //     foreach ($resultsByWord as $word => &$results) {
        //         usort($results['results'], function (IndexData $doc1, IndexData $doc2) {
        //             return $doc2->getTfidf() <=> $doc1->getTfidf();
        //         });

        //         $results['bestScore'] = $results['results'][0]->getTfidf();
        //     }

        //     uasort($resultsByWord, function ($doc1, $doc2) {
        //         return $doc2['bestScore'] <=> $doc1['bestScore'];
        //     });
        // }

        $intersectionResultsByMethod = [];
        foreach ($indexedResultsByMethod as $methodId => $resultsByWord) {
            if (!isset($intersectionResultsByMethod[$methodId])) {
                $intersectionResultsByMethod[$methodId] = [];
            }

            $resultsById = [];
            $intersectionIds = [];

            foreach ($resultsByWord as $word => $results) {
                $ids = [];

                /** @var IndexData */
                foreach ($results['results'] as $result) {
                    if ($result->getMethodId() === $methodId) {
                        $resultsById[$result->getDocumentId()] = $result;
                        $ids[] = $result->getDocumentId();
                    }
                }

                $intersectionIds[] = $ids;
            }

            if (count($intersectionIds) > 1) {
                $intersectionResults = array_intersect(...$intersectionIds);
            } else {
                $intersectionResults = $intersectionIds[0];
            }

            if (count($intersectionResults) > 0) {
                $restoredIntersectionResults = [];
                foreach ($intersectionResults as $id) {
                    $restoredIntersectionResults[] = $resultsById[$id];
                }
                $intersectionResults = $restoredIntersectionResults;

                uasort($intersectionResults, function (IndexData $doc1, IndexData $doc2) {
                    return $doc2->getTfidf() <=> $doc1->getTfidf();
                });

                $intersectionResultsByMethod[$methodId] = $intersectionResults;
            } else {
                $intersectionResultsByMethod[$methodId] = [];
            }
        }

        $documentsByMethod = [];
        foreach ($methodIds as $searchMethodId) {
            $documentsByMethod[$searchMethodId] = [];

            if (!isset($intersectionResultsByMethod[$searchMethodId])) {
                continue;
            }

            $bestScore = 0;
            /** @var IndexData */
            foreach ($intersectionResultsByMethod[$searchMethodId] as $indexData) {
                $id = $indexData->getDocumentId();

                if (isset($this->documents[$id])) {
                    $document = $this->documents[$id];

                    $documentsByMethod[$searchMethodId][] = $document;
                    $bestScore = max($bestScore, $document->getScore());
                }
            }

            $documentsByMethod[$searchMethodId] = array_filter(
                $documentsByMethod[$searchMethodId],
                fn (Document $r) => $r->getScore() >= ($bestScore / 2)
            );

            uasort($documentsByMethod[$searchMethodId], function (Document $doc1, Document $doc2) {
                return $doc2->getScore() <=> $doc1->getScore();
            });
        }

        $this->log('MEMORY USAGE ' . memory_get_usage());
        $this->log('MEMORY PEAK USAGE ' . memory_get_peak_usage());

        $executionTimer->end();

        $this->log("Guessed search language: {$language}");
        $this->log("Stemmed search query: " . implode(' ', $normalizedQueryWords));
        $this->log((string) $executionTimer);

        return $documentsByMethod;
    }

    private function createIndexData(string $indexData): IndexData
    {
        [$documentId, $tfidf] = explode('$', $indexData);
        $externalId = $this->documents[$documentId]->getExternalId();

        return new IndexData($documentId, $externalId, (float)$tfidf);
    }

    private function loadCollation(): void
    {
        $this->log('... Preparing collation');

        $this->collationMap = $this->collator->get();
        $this->collationKeys = array_keys($this->collationMap);
        $this->collationValues = array_values($this->collationMap);

        $this->log('... Done Preparing collation');
    }

    private function log(string $message)
    {
        if (!$this->debugging) {
            return;
        }

        $this->logger->debug($message);
    }
}
