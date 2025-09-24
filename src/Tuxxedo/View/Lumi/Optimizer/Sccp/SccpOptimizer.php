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
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;

// @todo Make BinaryOps for concat into an optimized ConcatNode
class SccpOptimizer extends AbstractOptimizer
{
    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $optimizedNodes = $this->optimizeNode($stream->consume());

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
        NodeInterface $node,
    ): array {
        return match (true) {
            $node instanceof AssignmentNode => parent::assignment($node),
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof BlockNode => parent::optimizeBlock($node),
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
            if ($node->operand->type === NativeType::STRING) {
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
                NativeType::NULL => null,
                NativeType::BOOL => \boolval($node->operand->operand),
                NativeType::INT => \intval($node->operand->operand),
                NativeType::FLOAT => \floatval($node->operand->operand),
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
            (
                $node->left instanceof LiteralNode ||
                $node->left instanceof IdentifierNode
            ) &&
            (
                $node->right instanceof LiteralNode ||
                $node->right instanceof IdentifierNode
            ) &&
            (
                $node->operator === BinarySymbol::ADD ||
                $node->operator === BinarySymbol::SUBTRACT ||
                $node->operator === BinarySymbol::MULTIPLY ||
                $node->operator === BinarySymbol::STRICT_EQUAL_EXPLICIT ||
                $node->operator === BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT ||
                $node->operator === BinarySymbol::GREATER ||
                $node->operator === BinarySymbol::LESS ||
                $node->operator === BinarySymbol::GREATER_EQUAL ||
                $node->operator === BinarySymbol::LESS_EQUAL ||
                $node->operator === BinarySymbol::AND ||
                $node->operator === BinarySymbol::OR
            )
        ) {
            if (!$node->left instanceof LiteralNode) {
                $value = $this->scope->get($node->left)->value;

                if (!$value instanceof LiteralNode) {
                    return [
                        $node,
                    ];
                }

                $leftNode = $value;
            } else {
                $leftNode = $node->left;
            }

            if (!$node->right instanceof LiteralNode) {
                $value = $this->scope->get($node->right)->value;

                if (!$value instanceof LiteralNode) {
                    return [
                        $node,
                    ];
                }

                $rightNode = $value;
            } else {
                $rightNode = $node->right;
            }

            if (
                $leftNode->type !== $rightNode->type &&
                $node->operator === BinarySymbol::STRICT_EQUAL_EXPLICIT
            ) {
                return [
                    new LiteralNode(
                        operand: 'false',
                        type: NativeType::BOOL,
                    ),
                ];
            }

            $left = $leftNode->type->cast($leftNode->operand);
            $right = $rightNode->type->cast($rightNode->operand);

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
                    BinarySymbol::ADD => $left + $right,
                    BinarySymbol::SUBTRACT => $left - $right,
                    BinarySymbol::MULTIPLY => $left * $right,
                    BinarySymbol::STRICT_EQUAL_EXPLICIT => $left === $right,
                    BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT => $left !== $right,
                    BinarySymbol::GREATER => $left > $right,
                    BinarySymbol::LESS => $left < $right,
                    BinarySymbol::GREATER_EQUAL => $left >= $right,
                    BinarySymbol::LESS_EQUAL => $left <= $right,
                    BinarySymbol::AND => \boolval($left) && \boolval($right),
                    BinarySymbol::OR => \boolval($left) || \boolval($right),
                };
            } else {
                $value = match ($node->operator) {
                    BinarySymbol::STRICT_EQUAL_EXPLICIT => $left === $right,
                    BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT => $left !== $right,
                    BinarySymbol::GREATER => $left > $right,
                    BinarySymbol::LESS => $left < $right,
                    BinarySymbol::GREATER_EQUAL => $left >= $right,
                    BinarySymbol::LESS_EQUAL => $left <= $right,
                    BinarySymbol::AND => \boolval($left) && \boolval($right),
                    BinarySymbol::OR => \boolval($left) || \boolval($right),
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
                    type: NativeType::fromValueNativeType($value),
                ),
            ];
        }

        return [
            $node,
        ];
    }
}
