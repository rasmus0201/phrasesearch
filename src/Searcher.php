<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use Bundsgaard\Phrasesearch\Entities\{Document, SearchResult};
use Bundsgaard\Phrasesearch\Indexes\InvertedIndex;
use Bundsgaard\Phrasesearch\Normalizers\Stemmer;
use Bundsgaard\Phrasesearch\Support\{ExecutionTimer, MemoryUsage};
use Psr\Log\{LoggerInterface, NullLogger};
use RuntimeException;
use SplFileObject;

class Searcher
{
    public const STOPWORDS_QUERY_LENGTH = 3;

    private Analyzer $analyzer;
    private ?LoggerInterface $logger = null;
    private InvertedIndex $invertedIndex;
    private SplFileObject $database;
    private bool $debugging = false;

    /** @var array<string, string[]> */
    private array $stopwords = [];

    public function __construct(
        Analyzer $analyzer,
        ?LoggerInterface $logger = null
    ) {
        $this->analyzer = $analyzer;
        $this->setLogger($logger);
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

    public function setStopwords(array $stopwords): void
    {
        $this->stopwords = $stopwords;
    }

    public function load(string $indexDataPath, string $databasePath): void
    {
        if (!file_exists($indexDataPath)) {
            throw new RuntimeException('Could not find index data');
        }

        if (!file_exists($databasePath)) {
            throw new RuntimeException('Could not find database');
        }

        $memoryUsage = new MemoryUsage();
        $memoryUsage->start();

        $this->log('... Loading inverse index');
        $this->invertedIndex = new InvertedIndex($indexDataPath);

        $this->database = new SplFileObject($databasePath);

        $memoryUsage->end();
        $this->log($memoryUsage->__toString());
        $this->log('... Done loading inverse index');
    }

    public function search(array $languages, string $query): SearchResult
    {
        if (empty($languages)) {
            throw new RuntimeException('Exepected non-empty method id');
        }

        $query = trim($query);
        if (empty($query)) {
            throw new RuntimeException('Exepected non-empty search query');
        }

        $executionTimer = new ExecutionTimer();
        $executionTimer->start();

        $memoryUsage = new MemoryUsage();
        $memoryUsage->start();

        $queryTerms = [];
        $numberQueryTerms = 0;
        foreach ($languages as $language) {
            $queryTerms[$language] = $this->analyzer->analyze($query, $language);
        }

        $numberQueryTerms = count(current($queryTerms));

        // Interestingly enough, this is way more
        // performant than array_intersect
        // Cut time to only 4% (Test: 50ms -> 2ms)
        // @see https://stackoverflow.com/a/53203232
        $intersector = function (array $arrayOne, array $arrayTwo) {
            if (count($arrayOne) === 0 || count($arrayTwo) === 0) {
                return [];
            }

            $index = array_flip($arrayOne);
            $second = array_flip($arrayTwo);

            $x = array_intersect_key($index, $second);

            return array_flip($x);
        };

        $matchedDocuments = [];
        foreach ($queryTerms as $language => $queryTerms) {
            $isFirst = true;

            foreach ($queryTerms as $queryTerm) {
                if (
                    $numberQueryTerms >= self::STOPWORDS_QUERY_LENGTH &&
                    isset($this->stopwords[$language]) &&
                    in_array($queryTerm, $this->stopwords[$language])
                ) {
                    continue;
                }

                $postingsList = $this->invertedIndex->get($language, $queryTerm, [])['documents'] ?? [];
                $matchedDocuments[$language] = $isFirst
                    ? $postingsList
                    : $intersector($matchedDocuments[$language], $postingsList);

                $isFirst = false;
            }
        }

        /** @var Documents[] */
        $documents = [];
        foreach ($matchedDocuments as $language => $postingsList) {
            foreach ($postingsList as $postingId) {
                $this->database->seek($postingId - 1);

                $documents[] = new Document(
                    $this->analyzer,
                    $this->invertedIndex,
                    $postingId,
                    $language,
                    $this->database->current()
                );
            }
        }

        $rawTokens = $this->analyzer->analyze($query, 'en', [
            'ignored_normalizers' => [Stemmer::class],
            'use_stop_words_doc_length' => 0
        ]);
        $searchResult = new SearchResult($rawTokens, $documents, $this->invertedIndex->count());

        $memoryUsage->end();
        $executionTimer->end();

        $this->log($memoryUsage->__toString());
        $this->log($executionTimer->__toString());

        return $searchResult;
    }

    private function log(string $message)
    {
        if (!$this->debugging) {
            return;
        }

        $this->logger->debug($message);
    }
}
