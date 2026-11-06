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
use Tuxxedo\View\Lumi\Syntax\Node\FilterCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\TernaryNode;
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

    public function testParsesNamedArgumentInFunctionCall(): void
    {
        $node = $this->helper->parse('date_local(when, format: \'d.m.Y\')');

        $this->assertFunctionCallNode(
            node: $node,
            expectedArgumentCount: 2,
        );

        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertSame(
            [
                0,
                'format',
            ],
            \array_keys($node->arguments),
        );

        $this->assertIdentifierNode(
            node: $node->arguments[0],
            expectedName: 'when',
        );

        $this->assertLiteralNode(
            node: $node->arguments['format'],
            expectedOperand: 'd.m.Y',
            expectedType: Type::STRING,
        );
    }

    public function testParsesOnlyNamedArgumentsInFunctionCall(): void
    {
        $node = $this->helper->parse('pick(b: 2, a: 1)');

        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertSame(
            [
                'b',
                'a',
            ],
            \array_keys($node->arguments),
        );
    }

    public function testParsesNamedArgumentWithTrailingComma(): void
    {
        $node = $this->helper->parse('foo(a, b: 1,)');

        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertSame(
            [
                0,
                'b',
            ],
            \array_keys($node->arguments),
        );
    }

    public function testParsesNamedArgumentInMethodCall(): void
    {
        $node = $this->helper->parse('user.format(style: \'long\')');

        $this->assertMethodCallNode(
            node: $node,
            expectedName: 'format',
            expectedArgumentCount: 1,
        );

        self::assertInstanceOf(MethodCallNode::class, $node);
        self::assertArrayHasKey('style', $node->arguments);
    }

    public function testParsesNamedArgumentInNullSafeMethodCall(): void
    {
        $node = $this->helper->parse('user?.format(style: \'long\')');

        self::assertInstanceOf(MethodCallNode::class, $node);
        self::assertTrue($node->nullSafe);
        self::assertArrayHasKey('style', $node->arguments);
    }

    public function testParsesNamedArgumentInFilterCall(): void
    {
        $node = $this->helper->parse('price | currency(locale: \'fo-FO\')');

        self::assertInstanceOf(FilterCallNode::class, $node);
        self::assertSame('currency', $node->name);
        self::assertSame(
            [
                'locale',
            ],
            \array_keys($node->arguments),
        );
    }

    public function testParsesTernaryAsPositionalArgument(): void
    {
        $node = $this->helper->parse('foo(a ? b : c)');

        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertSame(
            [
                0,
            ],
            \array_keys($node->arguments),
        );
        self::assertInstanceOf(TernaryNode::class, $node->arguments[0]);
    }

    public function testParsesTernaryAsNamedArgumentValue(): void
    {
        $node = $this->helper->parse('foo(x: a ? b : c)');

        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertInstanceOf(TernaryNode::class, $node->arguments['x']);
    }

    public function testParsesArrayWithBareKeyAsNamedArgumentValue(): void
    {
        $node = $this->helper->parse('foo(options: [key: 1])');

        self::assertInstanceOf(FunctionCallNode::class, $node);

        $this->assertArrayNode(
            node: $node->arguments['options'],
            expectedItemCount: 1,
        );
    }

    public function testThrowsOnPositionalArgumentAfterNamedArgument(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage('positional arguments cannot follow named arguments');

        $this->helper->parse('foo(a: 1, 2)');
    }

    public function testThrowsOnDuplicateNamedArgument(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage('named argument "a" is passed more than once');

        $this->helper->parse('foo(a: 1, a: 2)');
    }
}
