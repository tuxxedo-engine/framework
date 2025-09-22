<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

class NodeStream implements NodeStreamInterface
{
    public private(set) int $position = 0;

    /**
     * @param NodeInterface[] $nodes
     */
    public function __construct(
        public readonly array $nodes,
    ) {
    }

    public function __clone()
    {
        $this->position = 0;
    }

    public function eof(): bool
    {
        return $this->position === \sizeof($this->nodes);
    }

    public function current(): NodeInterface
    {
        if ($this->eof()) {
            throw ParserException::fromNodeStreamEof();
        }

        return $this->nodes[$this->position];
    }

    public function consume(): NodeInterface
    {
        if ($this->eof()) {
            throw ParserException::fromNodeStreamEof();
        }

        return $this->nodes[$this->position++];
    }
}
