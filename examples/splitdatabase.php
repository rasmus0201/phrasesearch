<?php

if (!$handle = fopen(__DIR__ . '/../data/database.csv', 'r')) {
    throw new RuntimeException('Could not load database');
}

if (!$newHandle = fopen(__DIR__ . '/../data/database-da-2.csv', 'w+')) {
    throw new RuntimeException('Could not open file');
}

$methodToLanguageMap = require 'methods.php';
while ($row = fgetcsv($handle)) {
    $methodId = $row[0];
    if (!isset($methodToLanguageMap[$methodId])) {
        continue;
    }

    $line = "\"{$row[0]}\",\"{$row[1]}\",{$row[2]},\"{$row[3]}\",{$row[2]}\n";
    fwrite($newHandle, $line);
}

fclose($handle);
fclose($newHandle);
