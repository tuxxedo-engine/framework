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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\LayoutBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class LayoutBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private LayoutBlockHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new LayoutBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testLayoutHandlerDirectiveIsLayout(): void
    {
        self::assertSame(
            'layout',
            $this->handler->directive,
        );
    }

    public function testLayoutHandlerLexWithStringLiteralEmitsLayoutToken(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: '\'base.lumi\'',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(1, $tokens);

        $this->assertLayoutToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'base.lumi',
        );
    }

    public function testLayoutHandlerLexThrowsWhenExpressionIsIdentifier(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: 'identifier',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testLayoutHandlerLexThrowsWhenExpressionIsCompound(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: '\'foo\' + \'bar\'',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testLayoutHandlerLexThrowsWhenExpressionIsEmpty(): void
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

    public function testLayoutHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 42,
            expression: '\'base.lumi\'',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertLayoutToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: 'base.lumi',
        );
    }
}
