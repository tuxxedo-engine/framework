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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\ExpressionParserHelper;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;

class BinaryMembershipExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesIn(): void
    {
        $node = $this->helper->parse('role in roles');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::IN,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'role',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'roles',
        );
    }

    public function testParsesNotIn(): void
    {
        $node = $this->helper->parse('id not in [1, 2]');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::NOT_IN,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);
        self::assertInstanceOf(ArrayNode::class, $node->right);
    }

    public function testInBindsLooserThanArithmetic(): void
    {
        $node = $this->helper->parse('a + 1 in list');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::IN,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertBinaryOpNode(
            node: $node->left,
            expectedOperator: BinarySymbol::ADD,
        );
    }

    public function testInBindsTighterThanLogicalAnd(): void
    {
        $node = $this->helper->parse('x in a and y');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::AND,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertBinaryOpNode(
            node: $node->left,
            expectedOperator: BinarySymbol::IN,
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: BinarySymbol}>
     */
    public static function wordOperatorDataProvider(): \Generator
    {
        yield 'and' => [
            'a and b',
            BinarySymbol::AND,
        ];

        yield 'or' => [
            'a or b',
            BinarySymbol::OR,
        ];

        yield 'xor' => [
            'a xor b',
            BinarySymbol::XOR,
        ];
    }

    #[DataProvider('wordOperatorDataProvider')]
    public function testWordOperatorsParseLikeTheirSymbols(
        string $source,
        BinarySymbol $expected,
    ): void {
        $this->assertBinaryOpNode(
            node: $this->helper->parse($source),
            expectedOperator: $expected,
        );
    }

    public function testReservedWordsStayUsableAsPropertyNames(): void
    {
        $node = $this->helper->parse('obj.not in obj.in');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::IN,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertPropertyAccessNode(
            node: $node->left,
            expectedProperty: 'not',
        );

        $this->assertPropertyAccessNode(
            node: $node->right,
            expectedProperty: 'in',
        );
    }
}
