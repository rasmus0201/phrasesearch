#!php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$databasePath = __DIR__ . '/../data/database-da-2.csv';
$methodToLanguagesMap = require __DIR__ . '/../data/methods.php';
if (!$handle = fopen($databasePath, 'r')) {
    throw new RuntimeException('Could not load database');
}

$documents = [];

$client = HttpClient::create();

while ($row = fgetcsv($handle)) {
    $language = $methodToLanguagesMap[$row[0]][0] ?? 'en';

    $documents[] = [
        'id' => $row[0].'$'.$row[3],
        'language' => $language,
        'fields' => [
            'headword' => $row[1],
        ],
    ];

    if (count($documents) === 50_000) {
        $response = $client->request(
            'POST',
            'http://127.0.0.1:9501/index',
            ['json' => $documents]
        );

        var_dump($response->getContent());

        $documents = [];
    }
}
