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

namespace Unit\View\Lumi\Lexer\Handler;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Lexer\TokenAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Handler\CommentTokenHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class CommentTokenHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private CommentTokenHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new CommentTokenHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testGetStartingSequenceReturnsCommentOpen(): void
    {
        self::assertSame(
            '{#',
            $this->handler->getStartingSequence(),
        );
    }

    public function testGetEndingSequenceReturnsCommentClose(): void
    {
        self::assertSame(
            '#}',
            $this->handler->getEndingSequence(),
        );
    }

    public function testTokenizeEmptyBufferProducesEmptyComment(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(1, $tokens);

        $this->assertCommentToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: '',
        );
    }

    public function testTokenizeBufferWithContentIsEmittedVerbatim(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: 'this is a note',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(1, $tokens);

        $this->assertCommentToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'this is a note',
        );
    }

    public function testTokenizeTrimsSurroundingWhitespace(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: '   padded comment   ',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(1, $tokens);

        $this->assertCommentToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'padded comment',
        );
    }

    public function testTokenizePreservesInteriorWhitespaceWhenTrimming(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: "  line one\nline two  ",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(1, $tokens);

        $this->assertCommentToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: "line one\nline two",
        );
    }

    public function testTokenizePropagatesStartingLine(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 42,
            buffer: 'note',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(1, $tokens);

        $this->assertCommentToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: 'note',
        );
    }
}
