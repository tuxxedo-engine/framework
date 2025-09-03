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

use Tuxxedo\View\Lumi\Node\NodeInterface;

interface NodeStreamInterface
{
    public int $position {
        get;
    }

    /**
     * @var NodeInterface[]
     */
    public array $nodes {
        get;
    }

    /**
     * @phpstan-impure
     */
    public function eof(): bool;

    /**
     * @throws ParserException
     *
     * @phpstan-impure
     */
    public function current(): NodeInterface;

    public function consume(): void;
}
