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
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

// @todo Support mode expressions
class ExpressionCompiler implements ExpressionCompilerInterface
{
    public function compile(
        NodeStreamInterface $stream,
    ): string {
        $node = $stream->current();

        if (!$node instanceof IdentifierNode) {
            throw CompilerException::fromUnexpectedNode(
                nodeClass: $node::class,
            );
        }

        $stream->consume();

        if (!$stream->eof()) {
            throw CompilerException::fromUnexpectedNode(
                nodeClass: ($stream->current())::class,
            );
        }

        return $this->compileIdentifier($node);
    }

    private function compileIdentifier(
        IdentifierNode $node,
    ): string {
        return \sprintf(
            '$%s',
            $node->name,
        );
    }
}
