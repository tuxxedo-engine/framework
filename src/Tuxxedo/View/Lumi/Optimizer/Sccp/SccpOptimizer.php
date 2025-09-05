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
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinaryOperator;

class SccpOptimizer extends AbstractOptimizer
{
    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $optimizedNodes = $this->optimizeNode($stream->current());

            if (\sizeof($optimizedNodes) > 0) {
                \array_push($nodes, ...$optimizedNodes);
            }

            $stream->consume();
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeNode(
        NodeInterface $node,
    ): array {
        return match (true) {
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof BinaryOpNode => $this->optimizeBinaryOp($node),
            $node instanceof EchoNode => $this->optimizeEcho($node),
            $node instanceof GroupNode => $this->optimizeGroup($node),
            default => [
                $node,
            ],
        };
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeEcho(
        EchoNode $node,
    ): array {
        if ($node->operand instanceof LiteralNode) {
            if ($node->operand->type === NodeNativeType::STRING) {
                if ($node->operand->operand === '') {
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

            $value = match ($node->operand->type) {
                NodeNativeType::NULL => null,
                NodeNativeType::BOOL => \boolval($node->operand->operand),
                NodeNativeType::INT => \intval($node->operand->operand),
                NodeNativeType::FLOAT => \floatval($node->operand->operand),
            };

            if ($value !== null) {
                return [
                    new TextNode(
                        text: (string)$value,
                    ),
                ];
            }

            return [];
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeGroup(
        GroupNode $node,
    ): array {
        if ($node->operand instanceof LiteralNode) {
            return [
                $node->operand,
            ];
        } elseif ($node->operand instanceof BinaryOpNode) {
            return $this->optimizeBinaryOp($node->operand);
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeBinaryOp(
        BinaryOpNode $node,
    ): array {
        if (
            $node->left instanceof LiteralNode &&
            $node->right instanceof LiteralNode &&
            (
                $node->operator === BinaryOperator::ADD ||
                $node->operator === BinaryOperator::SUBTRACT ||
                $node->operator === BinaryOperator::MULTIPLY ||
                $node->operator === BinaryOperator::STRICT_EQUAL_EXPLICIT ||
                $node->operator === BinaryOperator::STRICT_NOT_EQUAL_EXPLICIT ||
                $node->operator === BinaryOperator::GREATER ||
                $node->operator === BinaryOperator::LESS ||
                $node->operator === BinaryOperator::GREATER_EQUAL ||
                $node->operator === BinaryOperator::LESS_EQUAL ||
                $node->operator === BinaryOperator::AND ||
                $node->operator === BinaryOperator::OR
            )
        ) {
            if (
                $node->left->type !== $node->right->type &&
                $node->operator === BinaryOperator::STRICT_EQUAL_EXPLICIT
            ) {
                return [
                    new LiteralNode(
                        operand: 'false',
                        type: NodeNativeType::BOOL,
                    ),
                ];
            }

            $left = $node->left->cast();
            $right = $node->right->cast();

            if (
                (
                    \is_int($left) ||
                    \is_float($left)
                ) &&
                (
                    \is_int($right) ||
                    \is_float($right)
                )
            ) {
                $value = match ($node->operator) {
                    BinaryOperator::ADD => $left + $right,
                    BinaryOperator::SUBTRACT => $left - $right,
                    BinaryOperator::MULTIPLY => $left * $right,
                    BinaryOperator::STRICT_EQUAL_EXPLICIT => $left === $right,
                    BinaryOperator::STRICT_NOT_EQUAL_EXPLICIT => $left !== $right,
                    BinaryOperator::GREATER => $left > $right,
                    BinaryOperator::LESS => $left < $right,
                    BinaryOperator::GREATER_EQUAL => $left >= $right,
                    BinaryOperator::LESS_EQUAL => $left <= $right,
                    BinaryOperator::AND => \boolval($left) && \boolval($right),
                    BinaryOperator::OR => \boolval($left) || \boolval($right),
                };
            } else {
                $value = match ($node->operator) {
                    BinaryOperator::STRICT_EQUAL_EXPLICIT => $left === $right,
                    BinaryOperator::STRICT_NOT_EQUAL_EXPLICIT => $left !== $right,
                    BinaryOperator::GREATER => $left > $right,
                    BinaryOperator::LESS => $left < $right,
                    BinaryOperator::GREATER_EQUAL => $left >= $right,
                    BinaryOperator::LESS_EQUAL => $left <= $right,
                    BinaryOperator::AND => \boolval($left) && \boolval($right),
                    BinaryOperator::OR => \boolval($left) || \boolval($right),
                    default => null,
                };

                if ($value === null) {
                    return [
                        $node,
                    ];
                }
            }

            return [
                new LiteralNode(
                    operand: \strval($value),
                    type: NodeNativeType::fromValueNativeType($value),
                ),
            ];
        }

        return [
            $node,
        ];
    }
}
