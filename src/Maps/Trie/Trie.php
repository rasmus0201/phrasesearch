<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Maps\Trie;

class Trie
{
    private Node $root;

    function __construct()
    {
        $this->root = new Node(-1);
    }

    public function insert(string $string, int $weight): bool
    {
        $currentNode = $this->root;
        $chars = mb_str_split($string);

        foreach ($chars as $char) {
            $unicode = mb_ord($char);

            $currentNode->weight = max($weight, $currentNode->weight);
            if ($currentNode->isChild($unicode)) {
                $childNode = $currentNode->getChild($unicode);
            } else {
                $childNode = new Node($weight);
                $currentNode->addChild($unicode, $childNode);
            }

            $currentNode = $childNode;
        }

        return true;
    }

    public function getNodeWeight(string $string): int
    {
        $currentNode = $this->root;

        if ($string === '') {
            return -1;
        }

        $chars = mb_str_split($string);

        foreach ($chars as $char) {
            $unicode = mb_ord($char);
            if (!$currentNode->isChild($unicode)) {
                return -1;
            }

            $currentNode = $currentNode->getChild($unicode);
        }

        return $currentNode->weight;
    }
}
