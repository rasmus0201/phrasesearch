<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch;

use Bundsgaard\Phrasesearch\Indexes\InvertedIndex;
use Bundsgaard\Phrasesearch\Support\MemoryUsage;
use Psr\Log\{LoggerInterface, NullLogger};
use RuntimeException;
use SplFileObject;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Indexer
{
    private Analyzer $analyzer;
    private OutputInterface $output;
    private ?LoggerInterface $logger = null;
    private InvertedIndex $invertedIndex;
    private bool $debugging = false;

    private int $totalNumberOfDocuments = 0;

    public function __construct(
        Analyzer $analyzer,
        OutputInterface $output,
        ?LoggerInterface $logger = null
    ) {
        $this->analyzer = $analyzer;
        $this->output = $output;
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

    public function create(
        string $databasePath,
        string $indexDataPath,
        array $methodToLanguagesMap
    ): void {
        $this->log('... Starting index-creation');

        $memoryUsage = new MemoryUsage();
        $memoryUsage->start();

        if (!$handle = fopen($databasePath, 'r')) {
            throw new RuntimeException('Could not load database');
        }

        $lineCount = $this->getLineCount($databasePath);
        $progressBar = new ProgressBar($this->output, $lineCount);

        $supportedLanguages = array_unique(array_merge(...array_values($methodToLanguagesMap)));
        $this->invertedIndex = new InvertedIndex($indexDataPath, $supportedLanguages);

        $progressBar->start();
        $documentId = 1;
        while ($row = fgetcsv($handle)) {
            $language = $methodToLanguagesMap[$row[0]][0] ?? 'en';

            foreach ($this->analyzer->analyze($row[1], $language) as $term) {
                $this->invertedIndex->addDocument($language, $term, $documentId);
            }

            $documentId += 1;
            $progressBar->advance();
        }

        $this->totalNumberOfDocuments = $documentId - 1;

        $this->invertedIndex->store();

        fclose($handle);

        $memoryUsage->end();
        $progressBar->finish();
        $this->output->write(PHP_EOL);

        $this->log('... Index-creation memory: ' . $memoryUsage->__toString());
        $this->log('Done creating index');
    }

    private function getLineCount(string $path): int
    {
        $file = new SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);

        return $file->key();
    }

    private function log(string $message)
    {
        if (!$this->debugging) {
            return;
        }

        $this->logger->debug($message);
    }
}
