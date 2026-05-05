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

use Fixture\View\Lumi\Parser\ExpressionParser\UnknownToken;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\ExpressionParserHelper;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionParserTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesEmptyArrayLiteral(): void
    {
        $node = $this->helper->parse('[]');

        $this->assertArrayNode(
            node: $node,
            expectedItemCount: 0,
        );
    }

    public function testParsesTrailingCommaInArgumentList(): void
    {
        $node = $this->helper->parse('foo(a,)');

        $this->assertFunctionCallNode(
            node: $node,
            expectedArgumentCount: 1,
        );

        self::assertInstanceOf(FunctionCallNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->arguments[0],
            expectedName: 'a',
        );
    }

    public function testParsesTrailingCommaInArrayLiteral(): void
    {
        $node = $this->helper->parse('[1,]');

        $this->assertArrayNode(
            node: $node,
            expectedItemCount: 1,
        );

        self::assertInstanceOf(ArrayNode::class, $node);

        $this->assertArrayItemNode(
            node: $node->items[0],
        );
    }

    public function testParsesPreIncrementOnGroupedIdentifier(): void
    {
        $node = $this->helper->parse('++(x)');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::INCREMENT_PRE,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertGroupNode(
            node: $node->operand,
        );
    }

    public function testThrowsOnEmptyExpression(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parseTokens(
            tokens: [],
        );
    }

    public function testThrowsOnUnknownCharacterAtStart(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse(']');
    }

    public function testThrowsOnLeadingNullSafeAccess(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('?.foo');
    }

    public function testThrowsOnLeadingBareBinaryOperator(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('* a');
    }

    public function testThrowsOnNonIdentifierAfterDot(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('user.42');
    }

    public function testThrowsOnNonIdentifierAfterNullSafeAccess(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('user?.42');
    }

    public function testThrowsOnNonCharacterTokenWhereCloseExpected(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('foo(a b)');
    }

    public function testThrowsOnWrongCharacterTokenWhereCloseExpected(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('foo(a]');
    }

    public function testThrowsOnPreIncrementOfLiteral(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('++42');
    }

    public function testThrowsOnPreIncrementOfBinaryExpression(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('++(a + b)');
    }

    public function testThrowsOnLiteralTokenWithUnknownType(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parseTokens(
            tokens: [
                new LiteralToken(
                    line: 1,
                    op1: 'x',
                    op2: 'GIBBERISH',
                ),
            ],
        );
    }

    public function testThrowsOnUnknownTokenInPrefixPosition(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parseTokens(
            tokens: [
                new UnknownToken(
                    line: 1,
                ),
            ],
        );
    }

    public function testParsesIdentifierThroughLiteralTokenOfKnownType(): void
    {
        $node = $this->helper->parseTokens(
            tokens: [
                new LiteralToken(
                    line: 1,
                    op1: '7',
                    op2: Type::INT->name,
                ),
            ],
        );

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '7',
            expectedType: Type::INT,
        );
    }
}
