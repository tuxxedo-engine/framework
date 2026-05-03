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

namespace Unit\View\Lumi\Lexer\Expression;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Lexer\TokenAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionLexerTest extends TestCase
{
    use TokenAssertionsTrait;

    private ExpressionLexer $lexer;

    protected function setUp(): void
    {
        $this->lexer = new ExpressionLexer();
    }

    public function testLexSingleIdentifier(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'foo',
        );

        self::assertCount(1, $tokens);

        $this->assertIdentifierToken(
            token: $tokens[0],
            expectedOp1: 'foo',
        );
    }

    public function testLexIdentifierWithUnderscoreAndDigits(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'foo_bar123',
        );

        self::assertCount(1, $tokens);

        $this->assertIdentifierToken(
            token: $tokens[0],
            expectedOp1: 'foo_bar123',
        );
    }

    public function testLexDoubleQuotedString(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '"hello"',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'hello',
            expectedOp2: Type::STRING->name,
        );
    }

    public function testLexSingleQuotedString(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: "'hello'",
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'hello',
            expectedOp2: Type::STRING->name,
        );
    }

    public function testLexStringWithEscapedQuote(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '"foo\"bar"',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'foo\"bar',
            expectedOp2: Type::STRING->name,
        );
    }

    public function testLexStringWithEvenBackslashesClosesCorrectly(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '"foo\\\\"',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'foo\\\\',
            expectedOp2: Type::STRING->name,
        );
    }

    public function testLexInteger(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '42',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: '42',
            expectedOp2: Type::INT->name,
        );
    }

    public function testLexNegativeInteger(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '-42',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: '-42',
            expectedOp2: Type::INT->name,
        );
    }

    public function testLexFloat(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '3.14',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: '3.14',
            expectedOp2: Type::FLOAT->name,
        );
    }

    public function testLexFloatWithLeadingDot(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '.5',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: '.5',
            expectedOp2: Type::FLOAT->name,
        );
    }

    public function testLexFloatWithScientificNotation(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '1.5e2',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: '1.5e2',
            expectedOp2: Type::FLOAT->name,
        );
    }

    public function testLexBoolTrue(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'true',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'true',
            expectedOp2: Type::BOOL->name,
        );
    }

    public function testLexBoolFalse(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'false',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'false',
            expectedOp2: Type::BOOL->name,
        );
    }

    public function testLexBoolCaseInsensitive(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'TRUE',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'TRUE',
            expectedOp2: Type::BOOL->name,
        );
    }

    public function testLexNull(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'null',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'null',
            expectedOp2: Type::NULL->name,
        );
    }

    public function testLexNullCaseInsensitive(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'NULL',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: 'NULL',
            expectedOp2: Type::NULL->name,
        );
    }

    public function testLexIdentifierBeforeStringFlushesBuffer(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'foo"bar"',
        );

        self::assertCount(2, $tokens);

        $this->assertIdentifierToken(
            token: $tokens[0],
            expectedOp1: 'foo',
        );

        $this->assertLiteralToken(
            token: $tokens[1],
            expectedOp1: 'bar',
            expectedOp2: Type::STRING->name,
        );
    }

    public function testLexNegativeLeadingDotFloat(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '-.5',
        );

        self::assertCount(1, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: '-.5',
            expectedOp2: Type::FLOAT->name,
        );
    }

    public function testLexIntegerInExpressionExitsNumberLoopOnNonNumericChar(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '42 == foo',
        );

        self::assertCount(3, $tokens);

        $this->assertLiteralToken(
            token: $tokens[0],
            expectedOp1: '42',
            expectedOp2: Type::INT->name,
        );

        $this->assertOperatorToken(
            token: $tokens[1],
            expectedOp1: '==',
        );

        $this->assertIdentifierToken(
            token: $tokens[2],
            expectedOp1: 'foo',
        );
    }

    public function testLexBinaryOperator(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'foo + bar',
        );

        self::assertCount(3, $tokens);

        $this->assertIdentifierToken(
            token: $tokens[0],
            expectedOp1: 'foo',
        );

        $this->assertOperatorToken(
            token: $tokens[1],
            expectedOp1: '+',
        );

        $this->assertIdentifierToken(
            token: $tokens[2],
            expectedOp1: 'bar',
        );
    }

    public function testLexExpressionWithoutSpacesFlushesBuffer(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'foo+bar',
        );

        self::assertCount(3, $tokens);

        $this->assertIdentifierToken(
            token: $tokens[0],
            expectedOp1: 'foo',
        );

        $this->assertOperatorToken(
            token: $tokens[1],
            expectedOp1: '+',
        );

        $this->assertIdentifierToken(
            token: $tokens[2],
            expectedOp1: 'bar',
        );
    }

    public function testLexUnaryNegateProducesOperatorThenIdentifier(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '-foo',
        );

        self::assertCount(2, $tokens);

        $this->assertOperatorToken(
            token: $tokens[0],
            expectedOp1: '-',
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'foo',
        );
    }

    public function testLexGreedyOperatorMatchingProducesSingleToken(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '==',
        );

        self::assertCount(1, $tokens);

        $this->assertOperatorToken(
            token: $tokens[0],
            expectedOp1: '==',
        );
    }

    public function testLexTripleEqualsProducesSingleToken(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '===',
        );

        self::assertCount(1, $tokens);

        $this->assertOperatorToken(
            token: $tokens[0],
            expectedOp1: '===',
        );
    }

    public function testLexCharacterSymbol(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '(',
        );

        self::assertCount(1, $tokens);

        $this->assertCharacterToken(
            token: $tokens[0],
            expectedOp1: '(',
        );
    }

    public function testLexFunctionCallExpression(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: 'foo(bar, baz)',
        );

        self::assertCount(6, $tokens);

        $this->assertIdentifierToken(
            token: $tokens[0],
            expectedOp1: 'foo',
        );

        $this->assertCharacterToken(
            token: $tokens[1],
            expectedOp1: '(',
        );

        $this->assertIdentifierToken(
            token: $tokens[2],
            expectedOp1: 'bar',
        );

        $this->assertCharacterToken(
            token: $tokens[3],
            expectedOp1: ',',
        );

        $this->assertIdentifierToken(
            token: $tokens[4],
            expectedOp1: 'baz',
        );

        $this->assertCharacterToken(
            token: $tokens[5],
            expectedOp1: ')',
        );
    }

    public function testLexStartingLineIsAppliedToTokens(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 5,
            operand: 'foo',
        );

        self::assertSame(
            5,
            $tokens[0]->line,
        );
    }

    public function testLexThrowsOnEmptyExpression(): void
    {
        self::expectException(LexerException::class);

        $this->lexer->lex(
            startingLine: 1,
            operand: '',
        );
    }

    public function testLexThrowsOnWhitespaceOnlyExpression(): void
    {
        self::expectException(LexerException::class);

        $this->lexer->lex(
            startingLine: 1,
            operand: '   ',
        );
    }

    public function testLexThrowsOnUnclosedDoubleQuote(): void
    {
        self::expectException(LexerException::class);

        $this->lexer->lex(
            startingLine: 1,
            operand: '"hello',
        );
    }

    public function testLexThrowsOnUnclosedSingleQuote(): void
    {
        self::expectException(LexerException::class);

        $this->lexer->lex(
            startingLine: 1,
            operand: "'hello",
        );
    }

    public function testLexThrowsOnInvalidNumber(): void
    {
        self::expectException(LexerException::class);

        $this->lexer->lex(
            startingLine: 1,
            operand: '1.2.3',
        );
    }

    public function testLexThrowsOnUnknownSymbol(): void
    {
        self::expectException(LexerException::class);

        $this->lexer->lex(
            startingLine: 1,
            operand: '@',
        );
    }

    public function testLexSymbolPrefixReturnsFalseStopsGreedyMatch(): void
    {
        $tokens = $this->lexer->lex(
            startingLine: 1,
            operand: '=>',
        );

        self::assertCount(2, $tokens);

        $this->assertOperatorToken(
            token: $tokens[0],
            expectedOp1: '=',
        );

        $this->assertOperatorToken(
            token: $tokens[1],
            expectedOp1: '>',
        );
    }
}
