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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\SetBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class SetBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private SetBlockHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new SetBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testSetHandlerDirectiveIsSet(): void
    {
        self::assertSame(
            'set',
            $this->handler->directive,
        );
    }

    public function testSetHandlerLexWrapsAssignmentWithAssignAndEndTokens(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: "var = 'value'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(3, \count($tokens));

        $this->assertAssignToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testSetHandlerLexWrapsSingleIdentifierWithAssignAndEndTokens(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: 'var',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertAssignToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'var',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    public function testSetHandlerLexPropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 7,
            expression: 'var',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertAssignToken(
            token: $tokens[0],
            expectedLine: 7,
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 7,
        );
    }
}
