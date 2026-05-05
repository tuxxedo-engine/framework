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

namespace Unit\View\Lumi\Parser\Expression;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\ExpressionParserHelper;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class ArrayExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesMixedKeyedAndPositionalArrayLiteral(): void
    {
        $node = $this->helper->parse('[1, \'two\', name: value]');

        $this->assertArrayNode(
            node: $node,
            expectedItemCount: 3,
        );

        self::assertInstanceOf(ArrayNode::class, $node);

        $this->assertArrayItemNode(
            node: $node->items[0],
        );

        self::assertInstanceOf(ArrayItemNode::class, $node->items[0]);
        self::assertNull($node->items[0]->key);

        $this->assertLiteralNode(
            node: $node->items[0]->value,
            expectedOperand: '1',
            expectedType: Type::INT,
        );

        $this->assertArrayItemNode(
            node: $node->items[1],
        );

        self::assertInstanceOf(ArrayItemNode::class, $node->items[1]);
        self::assertNull($node->items[1]->key);

        $this->assertLiteralNode(
            node: $node->items[1]->value,
            expectedOperand: 'two',
            expectedType: Type::STRING,
        );

        $this->assertArrayItemNode(
            node: $node->items[2],
        );

        self::assertInstanceOf(ArrayItemNode::class, $node->items[2]);
        self::assertNotNull($node->items[2]->key);

        $this->assertIdentifierNode(
            node: $node->items[2]->key,
            expectedName: 'name',
        );

        $this->assertIdentifierNode(
            node: $node->items[2]->value,
            expectedName: 'value',
        );
    }
}
