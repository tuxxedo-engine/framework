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
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

readonly class NodeCompilerHandler implements NodeCompilerHandlerInterface
{
    /**
     * @var \Closure(NodeInterface $node, CompilerInterface $compiler): string
     */
    public \Closure $handler;

    /**
     * @template T of NodeInterface
     *
     * @param class-string<T> $nodeClassName
     * @param \Closure(T $node, CompilerInterface $compiler): string $handler
     */
    public function __construct(
        public string $nodeClassName,
        \Closure $handler,
    ) {
        /** @var \Closure(NodeInterface $node, CompilerInterface $compiler): string $handler */
        $this->handler = $handler;
    }
}
