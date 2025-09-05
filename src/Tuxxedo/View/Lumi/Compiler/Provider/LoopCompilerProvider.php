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
use Tuxxedo\View\Lumi\Syntax\Node\BreakNode;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;

class LoopCompilerProvider implements CompilerProviderInterface
{
    private function compileWhile(
        WhileNode $node,
        CompilerInterface $compiler,
    ): string {
        $output = \sprintf(
            '<?php while (%s): ?>',
            $compiler->compileExpression($node->operand),
        );

        foreach ($node->body as $child) {
            $output .= $compiler->compileNode($child);
        }

        $output .= '<?php endwhile; ?>';

        return $output;
    }

    private function compileDoWhile(
        DoWhileNode $node,
        CompilerInterface $compiler,
    ): string {
        $output = '<?php do { ?>';

        foreach ($node->body as $child) {
            $output .= $compiler->compileNode($child);
        }

        $output .= \sprintf(
            '<?php } while (%s); ?>',
            $compiler->compileExpression($node->operand),
        );

        return $output;
    }

    private function compileContinue(
        ContinueNode $node,
        CompilerInterface $compiler,
    ): string {
        if ($node->count !== null && $node->count > 1) {
            return \sprintf(
                '<?php continue %d; ?>',
                $node->count,
            );
        }

        return '<?php continue; ?>';
    }

    private function compileBreak(
        BreakNode $node,
        CompilerInterface $compiler,
    ): string {
        if ($node->count !== null && $node->count > 1) {
            return \sprintf(
                '<?php break %d; ?>',
                $node->count,
            );
        }

        return '<?php break; ?>';
    }

    private function compileFor(
        ForNode $node,
        CompilerInterface $compiler,
    ): string {
        $key = '';

        if ($node->key !== null) {
            $key = \sprintf(
                '%s => ',
                $compiler->compileExpression($node->key),
            );
        }

        $output = \sprintf(
            '<?php foreach (%s as %s%s): ?>',
            $compiler->compileExpression($node->iterator),
            $key,
            $compiler->compileExpression($node->value),
        );

        foreach ($node->body as $child) {
            $output .= $compiler->compileNode($child);
        }

        $output .= '<?php endforeach; ?>';

        return $output;
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: WhileNode::class,
            handler: $this->compileWhile(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: DoWhileNode::class,
            handler: $this->compileDoWhile(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: ContinueNode::class,
            handler: $this->compileContinue(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: BreakNode::class,
            handler: $this->compileBreak(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: ForNode::class,
            handler: $this->compileFor(...),
        );
    }
}
