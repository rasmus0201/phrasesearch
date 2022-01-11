<?php

declare(strict_types=1);

namespace Bundsgaard\Phrasesearch\Maps\Trie;

class Node
{
    public int $weight;

    /**
     * @var array<int, Node>
     */
    private array $children;

    function __construct(int $weight)
    {
        $this->weight = $weight;
        $this->children = [];
    }

    function addChild(int $char, Node $node): void
    {
        $this->children[$char] = $node;
    }

    function isChild(int $char): bool
    {
        return isset($this->children[$char]);
    }

    function getChild(int $char): Node
    {
        return $this->children[$char];
    }
}
