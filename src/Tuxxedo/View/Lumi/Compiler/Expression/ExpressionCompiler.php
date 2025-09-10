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

namespace Tuxxedo\View\Lumi\Compiler\Expression;

use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\BuiltinNodeKinds;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;

class ExpressionCompiler implements ExpressionCompilerInterface
{
    public function compile(
        NodeStreamInterface $stream,
        CompilerInterface $compiler,
    ): string {
        $node = $stream->current();
        $oldState = $compiler->state->swap(BuiltinNodeKinds::EXPRESSION->name);

        if ($node instanceof ExpressionNodeInterface) {
            $stream->consume();

            if (!$stream->eof()) {
                throw CompilerException::fromUnexpectedNode(
                    nodeClass: ($stream->current())::class,
                );
            }

            $compiledNode = $compiler->compileNode($node, $stream);

            $compiler->state->swap($oldState);

            return $compiledNode;
        }

        throw CompilerException::fromUnexpectedNode(
            nodeClass: $node::class,
        );
    }
}
