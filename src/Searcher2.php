<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use Bundsgaard\Phrasesearch\Indexes\InvertedIndex;
use Bundsgaard\Phrasesearch\Support\{ExecutionTimer, MemoryUsage};
use LanguageDetection\Language as LanguageDetector;
use Psr\Log\{LoggerInterface, NullLogger};
use RuntimeException;

class Searcher2
{
    private Analyzer $analyzer;
    private LanguageDetector $languageDetector;
    private ?LoggerInterface $logger = null;
    private InvertedIndex $invertedIndex;
    private bool $debugging = false;

    /** @var array<string, string[]> */
    private array $stopwords = [];

    public function __construct(
        Analyzer $analyzer,
        LanguageDetector $languageDetector,
        ?LoggerInterface $logger = null
    ) {
        $this->analyzer = $analyzer;
        $this->languageDetector = $languageDetector;
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

    public function load(string $indexDataPath): void
    {
        if (!file_exists($indexDataPath)) {
            throw new RuntimeException('Could not find index data');
        }

        $memoryUsage = new MemoryUsage();
        $memoryUsage->start();

        $this->log('... Loading inverse index');
        $this->invertedIndex = new InvertedIndex($indexDataPath);

        $memoryUsage->end();
        $this->log($memoryUsage->__toString());
        $this->log('... Done loading inverse index');
    }

    public function search(array $methodIds, string $query)
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

        $memoryUsage = new MemoryUsage();
        $memoryUsage->start();

        $guessedLanguages = $this->languageDetector->detect($query)->bestResults()->close();
        $language = key($guessedLanguages);

        $queryTerms = $this->analyzer->analyze($query, $language);
        $numberQueryTerms = count($queryTerms);

        // This is way more performant than array_intersect,
        // interestingly enough... Cut time to only 4% (Test: 50ms -> 2ms)
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

        $matchedPostings = [];
        $count = 0;
        foreach ($queryTerms as $queryTerm) {
            if (
                $numberQueryTerms > 2 &&
                isset($this->stopwords[$language]) &&
                in_array($queryTerm, $this->stopwords[$language])
            ) {
                continue;
            }

            if ($count === 0) {
                $matchedPostings = $this->invertedIndex->get($queryTerm)['documents'] ?? [];
            } else {
                $matchedPostings = $intersector(
                    $matchedPostings,
                    $this->invertedIndex->get($queryTerm)['documents'] ?? []
                );
            }

            $count += 1;
        }

        $matchedDocuments = $matchedPostings;

        $memoryUsage->end();
        $executionTimer->end();

        $this->log("Guessed search language: {$language}");
        $this->log($memoryUsage->__toString());
        $this->log($executionTimer->__toString());

        return $matchedDocuments;
    }

    private function log(string $message)
    {
        if (!$this->debugging) {
            return;
        }

        $this->logger->debug($message);
    }
}
