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

namespace Tuxxedo\View\Lumi\Compiler\Handler;

use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompilerInterface;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Node\TextNode;

class TextCompilerHandler implements CompilerHandlerInterface
{
    /**
     * @return class-string<NodeInterface>
     */
    public function getRootNodeClass(): string
    {
        return TextNode::class;
    }

    public function compile(
        NodeInterface $node,
        ExpressionCompilerInterface $expressionCompiler,
    ): string {
        /** @var TextNode $node */

        return $node->text;
    }
}
