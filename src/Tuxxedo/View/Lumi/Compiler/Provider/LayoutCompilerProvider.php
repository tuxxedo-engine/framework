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
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;

class LayoutCompilerProvider implements CompilerProviderInterface
{
    private function compileBlock(
        BlockNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        // @todo Implement

        return '';
    }

    private function compileLayout(
        LayoutNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        // @todo Implement

        return '';
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: BlockNode::class,
            handler: $this->compileBlock(...),
        );

        yield new PostNodeCompilerHandler(
            nodeClassName: LayoutNode::class,
            handler: $this->compileLayout(...),
        );
    }
}
