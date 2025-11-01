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
use Tuxxedo\View\Lumi\Syntax\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
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
            $node instanceof AssignmentNode => parent::assignment($node),
            $node instanceof BlockNode => [
                parent::optimizeBlockBody($node),
            ],
            $node instanceof BinaryOpNode => $this->optimizeBinaryOp($stream, $node),
            $node instanceof ConcatNode => $this->optimizeConcat($node),
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof EchoNode => $this->optimizeEcho($stream, $node),
            $node instanceof GroupNode => $this->optimizeGroup($stream, $node),
            $node instanceof TextNode => parent::optimizeText($stream, $node),
            $node instanceof UnaryOpNode => $this->optimizeUnaryOp($stream, $node),
            default => [
                $node,
            ],
        };
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
                Type::NULL => null,
                Type::BOOL => \boolval($node->operand->operand),
                Type::INT => \intval($node->operand->operand),
                Type::FLOAT => \floatval($node->operand->operand),
            };

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
        if ($node->operand instanceof LiteralNode) {
            return [
                $node->operand,
            ];
        } elseif ($node->operand instanceof BinaryOpNode) {
            return $this->optimizeBinaryOp($stream, $node->operand);
        }

        return [
            $node,
        ];
    }

    /**
     * @return NodeInterface[]
     */
    // @todo Migrate to evaluator
    private function optimizeBinaryOp(
        NodeStreamInterface $stream,
        BinaryOpNode $node,
    ): array {
        // @todo This can support variables too using evaluator
        if (
            $node->operator === BinarySymbol::NULL_COALESCE &&
            $node->left instanceof LiteralNode &&
            $node->left->type === Type::NULL
        ) {
            return [
                $node->right,
            ];
        }

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
                $node->operator === BinarySymbol::DIVIDE ||
                $node->operator === BinarySymbol::MODULUS ||
                $node->operator === BinarySymbol::STRICT_EQUAL_IMPLICIT ||
                $node->operator === BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT ||
                $node->operator === BinarySymbol::STRICT_EQUAL_EXPLICIT ||
                $node->operator === BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT ||
                $node->operator === BinarySymbol::GREATER ||
                $node->operator === BinarySymbol::LESS ||
                $node->operator === BinarySymbol::GREATER_EQUAL ||
                $node->operator === BinarySymbol::LESS_EQUAL ||
                $node->operator === BinarySymbol::AND ||
                $node->operator === BinarySymbol::OR ||
                $node->operator === BinarySymbol::BITWISE_AND ||
                $node->operator === BinarySymbol::BITWISE_OR ||
                $node->operator === BinarySymbol::BITWISE_XOR ||
                $node->operator === BinarySymbol::BITWISE_SHIFT_LEFT ||
                $node->operator === BinarySymbol::BITWISE_SHIFT_RIGHT ||
                $node->operator === BinarySymbol::XOR ||
                $node->operator === BinarySymbol::EXPONENTIATE
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
                (
                    $node->operator === BinarySymbol::STRICT_EQUAL_IMPLICIT ||
                    $node->operator === BinarySymbol::STRICT_EQUAL_EXPLICIT
                )
            ) {
                return [
                    LiteralNode::createBool(false),
                ];
            }

            $left = $this->evaluator->castNodeToValue($leftNode);
            $right = $this->evaluator->castNodeToValue($rightNode);

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
                if (
                    (
                        \floatval($right) === 0.0 &&
                        (
                            $node->operator === BinarySymbol::DIVIDE ||
                            $node->operator === BinarySymbol::MODULUS
                        )
                    )
                ) {
                    return [
                        $node,
                    ];
                }

                if ($node->operator === BinarySymbol::EXPONENTIATE) {
                    $left = (int) $left;
                    $right = (int) $right;

                    if ($left === 0 && $right < 0) {
                        return [
                            $node,
                        ];
                    }

                    return [
                        LiteralNode::createInt($left ** $right),
                    ];
                }

                $value = match ($node->operator) {
                    BinarySymbol::ADD => $left + $right,
                    BinarySymbol::SUBTRACT => $left - $right,
                    BinarySymbol::MULTIPLY => $left * $right,
                    BinarySymbol::DIVIDE => $left / $right,
                    BinarySymbol::MODULUS => $left % $right,
                    BinarySymbol::STRICT_EQUAL_IMPLICIT, BinarySymbol::STRICT_EQUAL_EXPLICIT => $left === $right,
                    BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT, BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT => $left !== $right,
                    BinarySymbol::GREATER => $left > $right,
                    BinarySymbol::LESS => $left < $right,
                    BinarySymbol::GREATER_EQUAL => $left >= $right,
                    BinarySymbol::LESS_EQUAL => $left <= $right,
                    BinarySymbol::AND => \boolval($left) && \boolval($right),
                    BinarySymbol::OR => \boolval($left) || \boolval($right),
                    BinarySymbol::BITWISE_AND => $left & $right,
                    BinarySymbol::BITWISE_OR => $left | $right,
                    BinarySymbol::BITWISE_XOR => $left ^ $right,
                    BinarySymbol::BITWISE_SHIFT_LEFT => $left << $right,
                    BinarySymbol::BITWISE_SHIFT_RIGHT => $left >> $right,
                    BinarySymbol::XOR => $left xor $right,
                };
            } else {
                $value = match ($node->operator) {
                    BinarySymbol::STRICT_EQUAL_IMPLICIT, BinarySymbol::STRICT_EQUAL_EXPLICIT => $left === $right,
                    BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT, BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT => $left !== $right,
                    BinarySymbol::GREATER => $left > $right,
                    BinarySymbol::LESS => $left < $right,
                    BinarySymbol::GREATER_EQUAL => $left >= $right,
                    BinarySymbol::LESS_EQUAL => $left <= $right,
                    BinarySymbol::AND => \boolval($left) && \boolval($right),
                    BinarySymbol::OR => \boolval($left) || \boolval($right),
                    BinarySymbol::XOR => $left xor $right,
                    default => null,
                };

                if ($value === null) {
                    return [
                        $node,
                    ];
                } elseif (\is_bool($value)) {
                    return [
                        LiteralNode::createBool($value),
                    ];
                }
            }

            return [
                new LiteralNode(
                    operand: \strval($value),
                    type: Type::fromValueNativeType($value),
                ),
            ];
        } elseif ($node->operator === BinarySymbol::CONCAT) {
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
            $node,
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

            if (
                $operand->type === Type::NULL ||
                (
                    $operand->type === Type::BOOL &&
                    $operand->operand === 'false'
                )
            ) {
                continue;
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
        NodeStreamInterface $stream,
        UnaryOpNode $node,
    ): array {
        $operand = $this->optimizeNode($stream, $node->operand);

        if (
            \sizeof($operand) !== 1 ||
            !$operand[0] instanceof ExpressionNodeInterface
        ) {
            return [
                $node,
            ];
        }

        if (!$operand[0] instanceof LiteralNode) {
            if ($node->operand !== $operand[0]) {
                return [
                    new UnaryOpNode(
                        operand: $operand[0],
                        operator: $node->operator,
                    ),
                ];
            }

            return [
                $node,
            ];
        }

        if ($node->operator === UnarySymbol::NOT) {
            return [
                LiteralNode::createBool(!\boolval($this->evaluator->castNodeToValue($operand[0]))),
            ];
        } elseif ($node->operator === UnarySymbol::BITWISE_NOT) {
            return [
                LiteralNode::createInt(~((int) $this->evaluator->castNodeToValue($operand[0]))),
            ];
        }

        return [
            $node,
        ];
    }
}
