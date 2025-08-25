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
use Tuxxedo\View\Lumi\Node\CommentNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;

class CommentCompilerHandler implements CompilerHandlerInterface
{
    /**
     * @return class-string<NodeInterface>
     */
    public function getRootNodeClass(): string
    {
        return CommentNode::class;
    }

    public function compile(
        NodeInterface $node,
        ExpressionCompilerInterface $expressionCompiler,
    ): string {
        /** @var CommentNode $node */

        $commentary = '';
        $lines = \preg_split('/\n/u', $node->text);

        if ($lines !== false) {
            foreach ($lines as $line) {
                $commentary .= \sprintf(
                    "<?php // %s ?>\n",
                    \mb_trim($line),
                );
            }
        }

        return $commentary;
    }
}
