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
use Tuxxedo\View\Lumi\Node\GroupNode;
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\NodeStream;

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
        $arguments = [];

        if (\sizeof($node->arguments) > 0) {
            foreach ($node->arguments as $argument) {
                $arguments[] = $compiler->compileNode($argument);
            }
        }

        return \sprintf(
            '$this->functionCall(\'%s\', [%s])',
            $node->name,
            \join(', ', $arguments),
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

        if (\mb_strtolower($caller) === '$this') {
            throw CompilerException::fromCannotCallThis();
        }

        $arguments = [];

        if (\sizeof($node->arguments) > 0) {
            foreach ($node->arguments as $argument) {
                $arguments[] = $compiler->compileNode($argument);
            }
        }

        return \sprintf(
            '$this->instanceCall(%s)->%s(...[%s])',
            $caller,
            $node->name,
            \join(', ', $arguments),
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
        if (\mb_strtolower($node->name->name) === 'this') {
            throw CompilerException::fromCannotOverrideThis();
        }

        return \sprintf(
            '<?php $%s %s %s; ?>',
            $node->name->name,
            $node->operator->symbol(),
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

    private function compileGroup(
        GroupNode $node,
        CompilerInterface $compiler,
    ): string {
        return \sprintf(
            '(%s)',
            $compiler->expressionCompiler->compile(
                stream: new NodeStream(
                    nodes: [
                        $node->operand,
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

        yield new NodeCompilerHandler(
            nodeClassName: GroupNode::class,
            handler: $this->compileGroup(...),
        );
    }
}
