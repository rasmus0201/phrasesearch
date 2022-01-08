<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use Bundsgaard\Phrasesearch\Entities\Document;
use Psr\Log\{LoggerInterface, NullLogger};
use RuntimeException;
use Wamania\Snowball\StemmerManager;

class Indexer
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

    private int $totalNumberOfDocuments = 0;

    /** @var array<string, array<string, true>> */
    private array $termCounts = [];

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

    /**
     * @param string $databasePath
     * @param array<string, string[]> $methodToLanguageMap
     *
     * @throws RuntimeException
     */
    public function loadDocuments(string $databasePath, array $methodToLanguageMap): void
    {
        $this->log('... Iterating database');

        if (!$handle = fopen($databasePath, 'r')) {
            throw new RuntimeException('Could not load database');
        }

        while ($row = fgetcsv($handle)) {
            $methodId = $row[0];
            if (!isset($methodToLanguageMap[$methodId])) {
                continue;
            }

            $sourceLanguage = $methodToLanguageMap[$methodId][0];
            $headword = $row[1];
            $lemmaId = $row[3];
            $externalId = $methodId . '$' . $lemmaId;

            $headword = str_replace(
                $this->collationKeys,
                $this->collationValues,
                $headword
            );

            $document = new Document(
                $this->stemmerManager,
                $this->totalNumberOfDocuments,
                $externalId,
                $sourceLanguage,
                $headword
            );

            foreach ($document->getTermFrequencies() as $term => $freq) {
                if (!isset($this->termCounts[$term])) {
                    $this->termCounts[$term] = [];
                }

                if (!isset($this->termCounts[$term][$document->getId()])) {
                    $this->termCounts[$term][$document->getId()] = true;
                }
            }

            $this->documents[$document->getId()] = $document;
            $this->totalNumberOfDocuments++;
        }

        fclose($handle);

        $this->log('Done iterating database');
    }

    public function save(string $documentsDataPath, string $indexDataPath): void
    {
        $this->log('... Saving data for index');

        if (!$documentsHandle = fopen($documentsDataPath, 'w+')) {
            throw new RuntimeException('Could not open file: ' . $documentsDataPath);
        }

        if (!$indexHandle = fopen($indexDataPath, 'w+')) {
            throw new RuntimeException('Could not open file: ' . $indexDataPath);
        }

        /** @var array<string, string[]> */
        $inverseIndex = [];

        foreach ($this->documents as $document) {
            $score = $document->getScore();

            foreach ($document->getTermFrequencies() as $term => $frequency) {
                $tf = round($frequency / $document->getTermCount(), 6);
                $idf = round(
                    1 + log(($this->totalNumberOfDocuments + 1) / (array_sum($this->termCounts[$term]) + 1)),
                    6
                );

                $tfidf = round($tf * $idf, 6);
                $score += $tfidf;

                $indexData = $document->getId() . '$' . $tfidf;

                if (!isset($inverseIndex[$term])) {
                    $inverseIndex[$term] = [$indexData];
                } else {
                    $inverseIndex[$term][] = $indexData;
                }
            }

            $document->setScore($score);
            fwrite($documentsHandle, serialize($document) . "\n");
        }
        $this->log('... Serializing inverse index');

        fwrite($indexHandle, serialize($inverseIndex));
        fclose($indexHandle);
        fclose($documentsHandle);


        // {"cat": ["docId1:tfidf"]}

        $this->log('Done with inverse index');
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
