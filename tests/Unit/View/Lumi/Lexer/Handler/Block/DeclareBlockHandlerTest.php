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

namespace Unit\View\Lumi\Lexer\Handler\Block;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Lexer\TokenAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\DeclareBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;
use Tuxxedo\View\Lumi\Syntax\Type;

class DeclareBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private DeclareBlockHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new DeclareBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testDeclareHandlerDirectiveIsDeclare(): void
    {
        self::assertSame(
            'declare',
            $this->handler->directive,
        );
    }

    public function testDeclareHandlerLexWithStringLiteralEmitsDeclareLiteralEndTokens(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: "name = 'value'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertDeclareToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'name',
        );

        $this->assertLiteralToken(
            token: $tokens[1],
            expectedOp1: 'value',
            expectedOp2: Type::STRING->name,
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    public function testDeclareHandlerLexTrimsWhitespaceAroundOperands(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: "   name   =   'value'   ",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertDeclareToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'name',
        );

        $this->assertLiteralToken(
            token: $tokens[1],
            expectedOp1: 'value',
            expectedOp2: Type::STRING->name,
        );
    }

    public function testDeclareHandlerLexThrowsWhenExpressionHasNoEqualsSign(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: 'name without equals',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testDeclareHandlerLexThrowsWhenLeftOperandIsEmpty(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: "= 'value'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testDeclareHandlerLexThrowsWhenRightOperandIsCompoundExpression(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: 'name = foo + bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testDeclareHandlerLexThrowsWhenRightOperandIsIdentifier(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: 'name = identifier',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testDeclareHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 42,
            expression: "name = 'value'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertDeclareToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: 'name',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 42,
        );
    }
}
