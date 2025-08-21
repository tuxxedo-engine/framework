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

namespace Tuxxedo\View\Lumi\Node;

readonly class BlockNode implements NodeInterface
{
    /**
     * @param NodeInterface[] $nodes
     */
    public function __construct(
        public array $nodes,
    ) {
    }
}
