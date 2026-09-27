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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\YieldBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class YieldBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private YieldBlockHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new YieldBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testYieldHandlerDirectiveIsYield(): void
    {
        self::assertSame(
            'yield',
            $this->handler->directive,
        );
    }

    public function testYieldHandlerLexWithIdentifierEmitsYieldToken(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: 'main',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(1, $tokens);

        $this->assertYieldToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'main',
        );
    }

    public function testYieldHandlerLexThrowsWhenExpressionIsStringLiteral(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: '\'main\'',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testYieldHandlerLexThrowsWhenExpressionIsCompound(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: 'foo bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testYieldHandlerLexThrowsWhenExpressionIsEmpty(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testYieldHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 42,
            expression: 'sidebar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertYieldToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: 'sidebar',
        );
    }
}
