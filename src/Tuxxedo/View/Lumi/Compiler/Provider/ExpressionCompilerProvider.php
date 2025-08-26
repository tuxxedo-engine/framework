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

use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;

class ExpressionCompilerProvider implements CompilerProviderInterface
{
    private function compileIdentifier(
        IdentifierNode $node,
        CompilerInterface $compiler,
    ): string {
        return \sprintf(
            '$%s',
            $node->name,
        );
    }

    private function compileLiteral(
        LiteralNode $node,
        CompilerInterface $compiler,
    ): string {
        return match ($node->type) {
            NodeNativeType::STRING => '\'' . \str_replace('\'', '\\\'', $node->operand) . '\'',
            default => $node->operand,
        };
    }

    /**
     * @throws CompilerException
     */
    private function compileFunctionCall(
        FunctionCallNode $node,
        CompilerInterface $compiler,
    ): string {
        if (\sizeof($node->arguments) > 0) {
            throw CompilerException::fromNotImplemented(
                feature: 'Function call arguments',
            );
        }

        return \sprintf(
            '$this->functionCall(\'%s\')',
            $node->name,
        );
    }

    /**
     * @throws CompilerException
     */
    private function compileMethodCall(
        MethodCallNode $node,
        CompilerInterface $compiler,
    ): string {
        $caller = $compiler->compileNode($node->caller);

        if (\sizeof($node->arguments) > 0) {
            throw CompilerException::fromNotImplemented(
                feature: 'Method call arguments',
            );
        }

        if ($caller === 'this') {
            throw CompilerException::fromCannotCallThis();
        }

        return \sprintf(
            '$%s->%s()',
            $caller,
            $node->name,
        );
    }

    /**
     * @throws CompilerException
     */
    private function compileBinaryOp(
        BinaryOpNode $node,
        CompilerInterface $compiler,
    ): string {
        return \sprintf(
            '%s %s %s',
            $compiler->compileNode($node->left),
            $node->operator->symbol(),
            $compiler->compileNode($node->right),
        );
    }

    private function compileAssignment(
        AssignmentNode $node,
        CompilerInterface $compiler,
    ): string {
        return \sprintf(
            '<?php $%s %s %s; ?>',
            $node->name->name,
            $node->operator?->symbol() ?? BinaryOperator::ASSIGN->symbol(),
            $compiler->expressionCompiler->compile(
                stream: new NodeStream(
                    nodes: [
                        $node->value,
                    ],
                ),
                compiler: $compiler,
            ),
        );
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: IdentifierNode::class,
            handler: $this->compileIdentifier(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: LiteralNode::class,
            handler: $this->compileLiteral(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: FunctionCallNode::class,
            handler: $this->compileFunctionCall(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: MethodCallNode::class,
            handler: $this->compileMethodCall(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: BinaryOpNode::class,
            handler: $this->compileBinaryOp(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: AssignmentNode::class,
            handler: $this->compileAssignment(...),
        );
    }
}
