<?php

declare(strict_types=1);
ini_set('memory_limit', '2048M');

require '../vendor/autoload.php';

use Bundsgaard\Phrasesearch\Tokenizers\ChineseTokenizer;
use Bundsgaard\Phrasesearch\Tokenizers\LatinTokenizer;

$string = '

jeg gik mig over sø og spegepølse. What about floats 123.32 and !?
gray-haired women
The     quick ("brown") fox can\'t jump 32.3 feet, right? Brr, it\'s 29.3°F!

danish       numbers: 10.100,10

der er ca. 20% af danskerne der oplever stress også kaldet 1/4-del

नमस्ते

Arabic: في نهاية الامر

';

$tokenizer = new LatinTokenizer();
$tokens = $tokenizer->tokenize($string);
var_dump($tokens);
echo "\n";

$tokenizer = new ChineseTokenizer();
$tokens = $tokenizer->tokenize("小明硕士毕业于中国科学院计算所，后在日本京都大学深造");
var_dump($tokens);
echo "\n";
