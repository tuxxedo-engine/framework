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
use Tuxxedo\View\Lumi\Node\EchoNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Node\TextNode;
use Tuxxedo\View\Lumi\Parser\NodeStream;

class EchoCompilerHandler implements CompilerHandlerInterface
{
    /**
     * @return class-string<NodeInterface>
     */
    public function getRootNodeClass(): string
    {
        return EchoNode::class;
    }

    public function compile(
        NodeInterface $node,
        ExpressionCompilerInterface $expressionCompiler,
    ): string {
        /** @var EchoNode $node */

        return \sprintf(
            '<?= %s; ?>',
            $expressionCompiler->compile(
                stream: new NodeStream(
                    nodes: [
                        $node->operand,
                    ],
                ),
            ),
        );
    }
}
