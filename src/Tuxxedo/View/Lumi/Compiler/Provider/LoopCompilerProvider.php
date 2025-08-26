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
use Tuxxedo\View\Lumi\Node\WhileNode;
use Tuxxedo\View\Lumi\Parser\NodeStream;

class LoopCompilerProvider implements CompilerProviderInterface
{
    private function compileWhile(
        WhileNode $node,
        CompilerInterface $compiler,
    ): string {
        $output = \sprintf(
            '<?php while (%s): ?>',
            $compiler->expressionCompiler->compile(
                stream: new NodeStream(
                    nodes: [
                        $node->operand,
                    ],
                ),
                compiler: $compiler,
            ),
        );

        foreach ($node->body as $child) {
            $output .= $compiler->compileNode($child);
        }

        $output .= '<?php endwhile; ?>';

        return $output;
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: WhileNode::class,
            handler: $this->compileWhile(...),
        );
    }
}
