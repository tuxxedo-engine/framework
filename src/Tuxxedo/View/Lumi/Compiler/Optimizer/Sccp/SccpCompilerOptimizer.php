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

namespace Tuxxedo\View\Lumi\Compiler\Optimizer\Sccp;

use Tuxxedo\View\Lumi\Compiler\Optimizer\AbstractOptimizer;
use Tuxxedo\View\Lumi\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Node\EchoNode;
use Tuxxedo\View\Lumi\Node\GroupNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Node\TextNode;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;

class SccpCompilerOptimizer extends AbstractOptimizer
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
            (
                $node->left->type === NodeNativeType::INT ||
                $node->left->type === NodeNativeType::FLOAT
            ) &&
            $node->right instanceof LiteralNode &&
            (
                $node->right->type === NodeNativeType::INT ||
                $node->right->type === NodeNativeType::FLOAT
            ) &&
            (
                $node->operator === BinaryOperator::ADD ||
                $node->operator === BinaryOperator::SUBTRACT ||
                $node->operator === BinaryOperator::MULTIPLY
            )
        ) {
            $left = $node->left->type === NodeNativeType::FLOAT
                ? \floatval($node->left->operand)
                : \intval($node->left->operand);

            $right = $node->right->type === NodeNativeType::INT
                ? \floatval($node->right->operand)
                : \intval($node->right->operand);

            $value = match ($node->operator) {
                BinaryOperator::ADD => $left + $right,
                BinaryOperator::SUBTRACT => $left - $right,
                BinaryOperator::MULTIPLY => $left * $right,
            };

            return [
                new LiteralNode(
                    operand: \strval($value),
                    type: \is_float($value)
                        ? NodeNativeType::FLOAT
                        : NodeNativeType::INT,
                ),
            ];
        }

        // @todo Support logical operators

        return [
            $node,
        ];
    }
}
