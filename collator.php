<?php

function getCollation($path)
{
    $collationHandle = fopen($path, 'r');
    $collationMap = [];
    while (($line = fgets($collationHandle)) !== false) {
        [$from, $to] = explode('=', $line);
        $collationMap[$from] = $to;
    }
    fclose($collationHandle);

    $collationMap["\n"] = "";
    $collationMap["\r"] = "";

    return $collationMap;
}
