<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Support\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

trait NodeAssertionsTrait
{
    private function assertIdentifierNode(
        NodeInterface $node,
        string $expectedName,
    ): void {
        self::assertInstanceOf(
            IdentifierNode::class,
            $node,
        );

        self::assertSame(
            $expectedName,
            $node->name,
        );
    }

    private function assertLiteralNode(
        NodeInterface $node,
        string $expectedOperand,
        Type $expectedType,
    ): void {
        self::assertInstanceOf(
            LiteralNode::class,
            $node,
        );

        self::assertSame(
            $expectedOperand,
            $node->operand,
        );

        self::assertSame(
            $expectedType,
            $node->type,
        );
    }

    private function assertUnaryOpNode(
        NodeInterface $node,
        UnarySymbol $expectedOperator,
    ): void {
        self::assertInstanceOf(
            UnaryOpNode::class,
            $node,
        );

        self::assertSame(
            $expectedOperator,
            $node->operator,
        );
    }

    private function assertBinaryOpNode(
        NodeInterface $node,
        BinarySymbol $expectedOperator,
    ): void {
        self::assertInstanceOf(
            BinaryOpNode::class,
            $node,
        );

        self::assertSame(
            $expectedOperator,
            $node->operator,
        );
    }

    private function assertGroupNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            GroupNode::class,
            $node,
        );
    }

    private function assertFilterOrBitwiseOrNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            FilterOrBitwiseOrNode::class,
            $node,
        );
    }

    private function assertArrayAccessNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            ArrayAccessNode::class,
            $node,
        );
    }

    private function assertArrayNode(
        NodeInterface $node,
        int $expectedItemCount,
    ): void {
        self::assertInstanceOf(
            ArrayNode::class,
            $node,
        );

        self::assertCount(
            $expectedItemCount,
            $node->items,
        );
    }

    private function assertArrayItemNode(
        NodeInterface $node,
    ): void {
        self::assertInstanceOf(
            ArrayItemNode::class,
            $node,
        );
    }

    private function assertPropertyAccessNode(
        NodeInterface $node,
        string $expectedProperty,
        bool $expectedNullSafe = false,
    ): void {
        self::assertInstanceOf(
            PropertyAccessNode::class,
            $node,
        );

        self::assertSame(
            $expectedProperty,
            $node->property,
        );

        self::assertSame(
            $expectedNullSafe,
            $node->nullSafe,
        );
    }

    private function assertFunctionCallNode(
        NodeInterface $node,
        int $expectedArgumentCount,
    ): void {
        self::assertInstanceOf(
            FunctionCallNode::class,
            $node,
        );

        self::assertCount(
            $expectedArgumentCount,
            $node->arguments,
        );
    }

    private function assertMethodCallNode(
        NodeInterface $node,
        string $expectedName,
        int $expectedArgumentCount,
        bool $expectedNullSafe = false,
    ): void {
        self::assertInstanceOf(
            MethodCallNode::class,
            $node,
        );

        self::assertSame(
            $expectedName,
            $node->name,
        );

        self::assertCount(
            $expectedArgumentCount,
            $node->arguments,
        );

        self::assertSame(
            $expectedNullSafe,
            $node->nullSafe,
        );
    }
}
