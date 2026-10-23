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
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\TernaryNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class TernaryExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesTernary(): void
    {
        $node = $this->helper->parse('a ? \'yes\' : \'no\'');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertNotNull($node->then);

        $this->assertIdentifierNode(
            node: $node->condition,
            expectedName: 'a',
        );

        $this->assertLiteralNode(
            node: $node->then,
            expectedOperand: 'yes',
            expectedType: Type::STRING,
        );

        $this->assertLiteralNode(
            node: $node->else,
            expectedOperand: 'no',
            expectedType: Type::STRING,
        );
    }

    public function testParsesShortTernary(): void
    {
        $node = $this->helper->parse('a ?: b');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertNull($node->then);

        $this->assertIdentifierNode(
            node: $node->condition,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->else,
            expectedName: 'b',
        );
    }

    public function testParsesShortTernaryWithSpaceBetweenSymbols(): void
    {
        $node = $this->helper->parse('a ? : b');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertNull($node->then);
    }

    public function testNestedTernaryInElseBranchIsRightAssociative(): void
    {
        $node = $this->helper->parse('a ? b : c ? d : e');

        self::assertInstanceOf(TernaryNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->condition,
            expectedName: 'a',
        );

        self::assertInstanceOf(TernaryNode::class, $node->else);

        $this->assertIdentifierNode(
            node: $node->else->condition,
            expectedName: 'c',
        );
    }

    public function testNestedTernaryInThenBranch(): void
    {
        $node = $this->helper->parse('a ? b ? c : d : e');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertInstanceOf(TernaryNode::class, $node->then);

        $this->assertIdentifierNode(
            node: $node->else,
            expectedName: 'e',
        );
    }

    public function testNullCoalesceBindsTighterThanTernary(): void
    {
        $node = $this->helper->parse('a ?? b ? c : d');

        self::assertInstanceOf(TernaryNode::class, $node);

        $this->assertBinaryOpNode(
            node: $node->condition,
            expectedOperator: BinarySymbol::NULL_COALESCE,
        );
    }

    public function testFilterInConditionBindsTighterThanTernary(): void
    {
        $node = $this->helper->parse('a | upper ? b : c');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->condition);
    }

    public function testFilterInElseBranchAppliesToElseBranchOnly(): void
    {
        $node = $this->helper->parse('a ? b : c | upper');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->else);

        $this->assertIdentifierNode(
            node: $node->then ?? $node->condition,
            expectedName: 'b',
        );
    }

    public function testComparisonConditionBindsTighterThanTernary(): void
    {
        $node = $this->helper->parse('score > 90 ? \'A\' : \'B\'');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertInstanceOf(BinaryOpNode::class, $node->condition);
    }

    public function testTernaryAsArrayItemValue(): void
    {
        $node = $this->helper->parse('[a ? b : c]');

        self::assertInstanceOf(ArrayNode::class, $node);
        self::assertCount(1, $node->items);
        self::assertNull($node->items[0]->key);
        self::assertInstanceOf(TernaryNode::class, $node->items[0]->value);
    }

    public function testTernaryAsKeyedArrayItemValue(): void
    {
        $node = $this->helper->parse('[k: a ? b : c]');

        self::assertInstanceOf(ArrayNode::class, $node);
        self::assertNotNull($node->items[0]->key);
        self::assertInstanceOf(TernaryNode::class, $node->items[0]->value);
    }

    public function testNullSafeAccessIsUnaffected(): void
    {
        $node = $this->helper->parse('a?.b');

        self::assertInstanceOf(PropertyAccessNode::class, $node);
        self::assertTrue($node->nullSafe);
    }

    public function testTernaryWithoutColonThrows(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('a ? b c');
    }
}
