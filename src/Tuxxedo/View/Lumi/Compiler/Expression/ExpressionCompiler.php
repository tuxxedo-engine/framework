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
use Tuxxedo\View\Lumi\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

// @todo Support mode expressions
class ExpressionCompiler implements ExpressionCompilerInterface
{
    public function compile(
        NodeStreamInterface $stream,
    ): string {
        $node = $stream->current();
        $compiledNode = null;

        if ($node instanceof IdentifierNode) {
            $stream->consume();

            $compiledNode = $this->compileIdentifier($node);
        } elseif ($node instanceof LiteralNode) {
            $stream->consume();

            $compiledNode = $this->compileLiteral($node);
        } elseif ($node instanceof FunctionCallNode) {
            $stream->consume();

            $compiledNode = $this->compileFunctionCall($node);
        } elseif ($node instanceof MethodCallNode) {
            $stream->consume();

            $compiledNode = $this->compileMethodCall($node);
        }

        if ($compiledNode === null) {
            throw CompilerException::fromUnexpectedNode(
                nodeClass: $node::class,
            );
        }

        if (!$stream->eof()) {
            throw CompilerException::fromUnexpectedNode(
                nodeClass: ($stream->current())::class,
            );
        }

        return $compiledNode;
    }

    private function compileIdentifier(
        IdentifierNode $node,
    ): string {
        return \sprintf(
            '$%s',
            $node->name,
        );
    }

    private function compileLiteral(
        LiteralNode $node,
    ): string {
        return match ($node->type) {
            NodeNativeType::STRING => '\'' . \str_replace('\'', '\\\'', $node->operand) . '\'',
            default => $node->operand,
        };
    }

    private function compileFunctionCall(
        FunctionCallNode $node,
    ): string {
        if (\sizeof($node->arguments) > 0) {
            throw CompilerException::fromNotImplemented(
                feature: 'Function call arguments',
            );
        }

        return \sprintf(
            '%s()',
            $node->name,
        );
    }

    private function compileMethodCall(
        MethodCallNode $node,
    ): string {
        if (!$node->caller instanceof IdentifierNode) {
            throw CompilerException::fromNotImplemented(
                feature: 'More expressive method calls',
            );
        } elseif (\sizeof($node->arguments) > 0) {
            throw CompilerException::fromNotImplemented(
                feature: 'Method call arguments',
            );
        }

        return \sprintf(
            '$%s->%s()',
            $node->caller->name,
            $node->name,
        );
    }
}
