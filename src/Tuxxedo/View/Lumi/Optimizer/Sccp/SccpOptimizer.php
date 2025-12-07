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

namespace Tuxxedo\View\Lumi\Optimizer\Sccp;

use Tuxxedo\View\Lumi\Optimizer\AbstractOptimizer;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class SccpOptimizer extends AbstractOptimizer
{
    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $optimizedNodes = $this->optimizeNode($stream, $stream->consume());

            if (\sizeof($optimizedNodes) > 0) {
                \array_push($nodes, ...$optimizedNodes);
            }
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeNode(
        NodeStreamInterface $stream,
        NodeInterface $node,
    ): array {
        return match (true) {
            $node instanceof AssignmentNode => $this->optimizeAssignment($node),
            $node instanceof BlockNode => [
                parent::optimizeBlockBody($node),
            ],
            $node instanceof BinaryOpNode => $this->optimizeBinaryOp($stream, $node),
            $node instanceof ConcatNode => $this->optimizeConcat($node),
            $node instanceof ConditionalNode => $this->optimizeConditional($stream, $node),
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof EchoNode => $this->optimizeEcho($stream, $node),
            $node instanceof GroupNode => $this->optimizeGroup($stream, $node),
            $node instanceof TextNode => parent::optimizeText($stream, $node),
            $node instanceof UnaryOpNode => $this->optimizeUnaryOp($node),
            default => [
                $node,
            ],
        };
    }

    /**
     * @return AssignmentNode[]
     */
    private function optimizeAssignment(
        AssignmentNode $node,
    ): array {
        $this->scope->assign($node);

        if ($node->name instanceof IdentifierNode) {
            $variable = $this->scope->get($node->name);

            if ($variable->hasComputedValue()) {
                return [
                    new AssignmentNode(
                        name: $node->name,
                        value: LiteralNode::createFromNativeType($variable->computedValue),
                        operator: AssignmentSymbol::ASSIGN,
                    ),
                ];
            }
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeEcho(
        NodeStreamInterface $stream,
        EchoNode $node,
    ): array {
        if ($node->operand instanceof LiteralNode) {
            if ($node->operand->type === Type::STRING) {
                if (\mb_strlen($node->operand->operand) === 0) {
                    return [];
                } elseif (!$this->directives->asBool('lumi.autoescape')) {
                    return [
                        new TextNode(
                            text: $node->operand->operand,
                        ),
                    ];
                }

                return [
                    $node,
                ];
            }

            $value = $this->evaluator->castNodeToValue($node->operand);

            if ($value !== null) {
                return [
                    new TextNode(
                        text: (string) $value,
                    ),
                ];
            }

            return [];
        }

        $operand = $this->optimizeNode($stream, $node->operand);

        if (
            \sizeof($operand) === 1 &&
            $operand[0] !== $node->operand &&
            $operand[0] instanceof ExpressionNodeInterface
        ) {
            if ($operand[0] instanceof LiteralNode) {
                return [
                    new TextNode(
                        text: (string) $this->evaluator->castNodeToValue($operand[0]),
                    ),
                ];
            }

            return [
                new EchoNode(
                    operand: $operand[0],
                ),
            ];
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeGroup(
        NodeStreamInterface $stream,
        GroupNode $node,
    ): array {
        $node = $this->evaluator->dereferenceGroup($node);

        if ($node instanceof BinaryOpNode) {
            return $this->optimizeBinaryOp($stream, $node);
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeBinaryOp(
        NodeStreamInterface $stream,
        BinaryOpNode $node,
    ): array {
        if ($node->operator === BinarySymbol::CONCAT) {
            $operands = [];

            $this->collectConcatOperands($node, $operands);

            return [
                ...$this->optimizeNode(
                    stream: $stream,
                    node: new ConcatNode(
                        operands: $operands,
                    ),
                ),
            ];
        }

        return [
            $this->evaluator->binaryOp($this->scope, $node) ?? $node,
        ];
    }

    /**
     * @param ExpressionNodeInterface[] $operands
     */
    private function collectConcatOperands(
        ExpressionNodeInterface $node,
        array &$operands,
    ): void {
        if (
            $node instanceof BinaryOpNode &&
            $node->operator === BinarySymbol::CONCAT
        ) {
            $this->collectConcatOperands($node->left, $operands);
            $this->collectConcatOperands($node->right, $operands);

            return;
        }

        $operands[] = $node;
    }

    /**
     * @return ExpressionNodeInterface[]
     */
    private function optimizeConcat(
        ConcatNode $node,
    ): array {
        $literal = '';

        foreach ($node->operands as $operand) {
            if (!$operand instanceof LiteralNode) {
                return [
                    $node,
                ];
            }

            $literal .= $this->evaluator->castNodeToValue($operand);
        }

        return [
            LiteralNode::createString($literal),
        ];
    }

    /**
     * @return array{0: ExpressionNodeInterface}
     */
    private function optimizeUnaryOp(
        UnaryOpNode $node,
    ): array {
        return [
            $this->evaluator->unaryOp($this->scope, $node) ?? $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeConditional(
        NodeStreamInterface $stream,
        ConditionalNode $node,
    ): array {
        $branches = [];
        $condition = $this->optimizeNode($stream, $node->operand);

        foreach ($node->branches as $branch) {
            $optimizedBranch = $this->optimizeNode($stream, $node->operand);

            if (
                \sizeof($optimizedBranch) === 1 &&
                $optimizedBranch[0] !== $branch->operand &&
                $optimizedBranch[0] instanceof ExpressionNodeInterface
            ) {
                $branches[] = new ConditionalBranchNode(
                    operand: $optimizedBranch[0],
                    body: parent::optimizeNodes($branch->body),
                );
            } else {
                $branches[] = new ConditionalBranchNode(
                    operand: $branch->operand,
                    body: parent::optimizeNodes($branch->body),
                );
            }
        }

        if (
            \sizeof($condition) === 1 &&
            $condition[0] !== $node->operand &&
            $condition[0] instanceof ExpressionNodeInterface
        ) {
            return [
                new ConditionalNode(
                    operand: $condition[0],
                    body: parent::optimizeNodes($node->body),
                    else: parent::optimizeNodes($node->else),
                    branches: $branches,
                ),
            ];
        }

        return [
            new ConditionalNode(
                operand: $node->operand,
                body: parent::optimizeNodes($node->body),
                else: parent::optimizeNodes($node->else),
                branches: $branches,
            ),
        ];
    }
}
