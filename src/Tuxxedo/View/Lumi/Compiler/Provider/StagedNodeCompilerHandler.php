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

namespace Tuxxedo\View\Lumi\Compiler\Provider;

use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

readonly class StagedNodeCompilerHandler implements StagedNodeCompilerHandlerInterface
{
    /**
     * @param \Closure(NodeInterface $node, CompilerInterface $compiler, NodeStreamInterface $stream): string $handler
     */
    public function __construct(
        public NodeInterface $node,
        public \Closure $handler,
    ) {
    }
}
