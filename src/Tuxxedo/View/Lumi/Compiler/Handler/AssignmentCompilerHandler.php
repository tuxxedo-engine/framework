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
use Tuxxedo\View\Lumi\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Node\EchoNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\AssignmentOperator;

class AssignmentCompilerHandler implements CompilerHandlerInterface
{
    /**
     * @return class-string<NodeInterface>
     */
    public function getRootNodeClass(): string
    {
        return AssignmentNode::class;
    }

    public function compile(
        NodeInterface $node,
        ExpressionCompilerInterface $expressionCompiler,
    ): string {
        /** @var AssignmentNode $node */

        return \sprintf(
            '<?php $%s %s %s; ?>',
            $node->name->name,
            $node->operator?->symbol() ?? '=',
            $expressionCompiler->compile(
                stream: new NodeStream(
                    nodes: [
                        $node->value,
                    ],
                ),
            ),
        );
    }
}
