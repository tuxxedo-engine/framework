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
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;

class ConditionalCompilerProvider implements CompilerProviderInterface
{
    private function compileIf(
        ConditionalNode $node,
        CompilerInterface $compiler,
    ): string {
        $output = \sprintf(
            '<?php if (%s): ?>',
            $compiler->compileExpression($node->operand),
        );

        foreach ($node->body as $child) {
            $output .= $compiler->compileNode($child);
        }

        foreach ($node->branches as $branch) {
            $output .= \sprintf(
                '<?php elseif (%s): ?>',
                $compiler->compileExpression($branch->operand),
            );

            foreach ($branch->body as $child) {
                $output .= $compiler->compileNode($child);
            }
        }

        if (\sizeof($node->else) > 0) {
            $output .= '<?php else: ?>';

            foreach ($node->else as $child) {
                $output .= $compiler->compileNode($child);
            }
        }

        $output .= '<?php endif; ?>';

        return $output;
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: ConditionalNode::class,
            handler: $this->compileIf(...),
        );
    }
}
