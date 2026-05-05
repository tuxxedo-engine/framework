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

namespace Unit\View\Lumi\Lexer;

use Fixture\View\Lumi\Lexer\Lexer\IfCloseHandler;
use Fixture\View\Lumi\Lexer\Lexer\IfCloseToken;
use Fixture\View\Lumi\Lexer\Lexer\IfOpenHandler;
use Fixture\View\Lumi\Lexer\Lexer\IfOpenToken;
use Fixture\View\Lumi\Lexer\Lexer\StateLeakingHandler;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Handler\TokenHandlerInterface;
use Tuxxedo\View\Lumi\Lexer\Lexer;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class LexerTest extends TestCase
{
    private const string FIXTURE_FILE = __DIR__ . '/../../../../Fixture/View/Lumi/Lexer/Lexer/sample.lumi';

    public function testCreateDefaultExpressionLexerReturnsExpressionLexer(): void
    {
        self::assertInstanceOf(
            ExpressionLexer::class,
            Lexer::createDefaultExpressionLexer(),
        );
    }

    public function testCreateDefaultLexerStateReturnsLexerState(): void
    {
        self::assertInstanceOf(
            LexerState::class,
            Lexer::createDefaultLexerState(),
        );
    }

    public function testCreateWithoutDefaultHandlersExposesProvidedDependencies(): void
    {
        $expressionLexer = new ExpressionLexer();
        $state = new LexerState();

        $lexer = Lexer::createWithoutDefaultHandlers(
            expressionLexer: $expressionLexer,
            state: $state,
        );

        self::assertSame($expressionLexer, $lexer->expressionLexer);
        self::assertSame($state, $lexer->state);
    }

    public function testCreateWithoutDefaultHandlersFallsBackToDefaults(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers();

        self::assertInstanceOf(ExpressionLexer::class, $lexer->expressionLexer);
        self::assertInstanceOf(LexerState::class, $lexer->state);
    }

    public function testCreateDefaultHandlersReturnsTokenHandlerInstances(): void
    {
        $handlers = Lexer::createDefaultHandlers();

        self::assertNotEmpty($handlers);

        foreach ($handlers as $handler) {
            self::assertInstanceOf(TokenHandlerInterface::class, $handler);
        }
    }

    public function testCreateWithDefaultHandlersConstructsLexer(): void
    {
        $lexer = Lexer::createWithDefaultHandlers();

        self::assertInstanceOf(Lexer::class, $lexer);
        self::assertInstanceOf(ExpressionLexer::class, $lexer->expressionLexer);
        self::assertInstanceOf(LexerState::class, $lexer->state);
    }

    public function testCreateWithDefaultHandlersAcceptsAdditionalHandlers(): void
    {
        $lexer = Lexer::createWithDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
            ],
        );

        self::assertInstanceOf(Lexer::class, $lexer);
    }

    public function testEmptyInputProducesEmptyTokenStream(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers();

        $stream = $lexer->tokenizeByString('');

        self::assertSame([], $stream->tokens);
    }

    public function testPlainTextWithNoHandlersBecomesSingleTextToken(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers();

        $stream = $lexer->tokenizeByString('hello world');

        self::assertCount(1, $stream->tokens);
        self::assertInstanceOf(TextToken::class, $stream->tokens[0]);
        self::assertSame('hello world', $stream->tokens[0]->op1);
        self::assertSame(1, $stream->tokens[0]->line);
    }

    public function testDuplicateStartingSequenceThrowsLexerException(): void
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('Duplicate sequence "{if " encountered in lexer configuration');

        Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
                new IfOpenHandler(),
            ],
        );
    }

    public function testSingleHandlerProducesExpectedToken(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
            ],
        );

        $stream = $lexer->tokenizeByString('{if user.isActive}');

        self::assertCount(1, $stream->tokens);
        self::assertInstanceOf(IfOpenToken::class, $stream->tokens[0]);
        self::assertSame('user.isActive', $stream->tokens[0]->op1);
        self::assertSame(1, $stream->tokens[0]->line);
    }

    public function testTextSurroundingSingleHandlerEmitsAdjacentTextTokens(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
            ],
        );

        $stream = $lexer->tokenizeByString('before {if x} after');

        self::assertCount(3, $stream->tokens);

        self::assertInstanceOf(TextToken::class, $stream->tokens[0]);
        self::assertSame('before ', $stream->tokens[0]->op1);

        self::assertInstanceOf(IfOpenToken::class, $stream->tokens[1]);
        self::assertSame('x', $stream->tokens[1]->op1);

        self::assertInstanceOf(TextToken::class, $stream->tokens[2]);
        self::assertSame(' after', $stream->tokens[2]->op1);
    }

    public function testIfOpenAndCloseHandlersComposeAroundBodyText(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
                new IfCloseHandler(),
            ],
        );

        $stream = $lexer->tokenizeByString('{if user.isActive}Something{/if}');

        self::assertCount(3, $stream->tokens);

        self::assertInstanceOf(IfOpenToken::class, $stream->tokens[0]);
        self::assertSame('user.isActive', $stream->tokens[0]->op1);
        self::assertSame(1, $stream->tokens[0]->line);

        self::assertInstanceOf(TextToken::class, $stream->tokens[1]);
        self::assertSame('Something', $stream->tokens[1]->op1);
        self::assertSame(1, $stream->tokens[1]->line);

        self::assertInstanceOf(IfCloseToken::class, $stream->tokens[2]);
        self::assertSame(1, $stream->tokens[2]->line);
    }

    public function testIfBlockTracksLinesAcrossMultilineBody(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
                new IfCloseHandler(),
            ],
        );

        $stream = $lexer->tokenizeByString("{if user.isActive}\n    Something\n{/if}");

        self::assertCount(3, $stream->tokens);

        self::assertInstanceOf(IfOpenToken::class, $stream->tokens[0]);
        self::assertSame(1, $stream->tokens[0]->line);

        self::assertInstanceOf(TextToken::class, $stream->tokens[1]);
        self::assertSame("\n    Something\n", $stream->tokens[1]->op1);

        self::assertInstanceOf(IfCloseToken::class, $stream->tokens[2]);
        self::assertSame(3, $stream->tokens[2]->line);
    }

    public function testHandlerDispatchedAfterLeadingNewlinesReportsCorrectLine(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
            ],
        );

        $stream = $lexer->tokenizeByString("\n\n{if x}");

        self::assertCount(2, $stream->tokens);

        self::assertInstanceOf(TextToken::class, $stream->tokens[0]);
        self::assertSame("\n\n", $stream->tokens[0]->op1);

        self::assertInstanceOf(IfOpenToken::class, $stream->tokens[1]);
        self::assertSame(3, $stream->tokens[1]->line);
    }

    public function testEscapedStartingSequenceIsEmittedAsLiteralText(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
            ],
        );

        $stream = $lexer->tokenizeByString('\\{if x}');

        self::assertCount(1, $stream->tokens);
        self::assertInstanceOf(TextToken::class, $stream->tokens[0]);
        self::assertSame('{if x}', $stream->tokens[0]->op1);
    }

    public function testUnterminatedHandlerMatchEmitsLiteralTextFallback(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
            ],
        );

        $stream = $lexer->tokenizeByString('{if user.isActive');

        self::assertCount(1, $stream->tokens);
        self::assertInstanceOf(TextToken::class, $stream->tokens[0]);
        self::assertSame('{if user.isActive', $stream->tokens[0]->op1);
        self::assertSame(1, $stream->tokens[0]->line);
    }

    public function testHandlerLeavingStateDirtyThrowsLexerException(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new StateLeakingHandler(),
            ],
        );

        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('Lexer state was left in an unclean state, possible end of sequence tag missing');

        $lexer->tokenizeByString('{leak}');
    }

    public function testTokenizeByFileProducesSameTokensAsTokenizeByString(): void
    {
        $lexer = Lexer::createWithoutDefaultHandlers(
            handlers: [
                new IfOpenHandler(),
                new IfCloseHandler(),
            ],
        );

        $stream = $lexer->tokenizeByFile(self::FIXTURE_FILE);

        self::assertCount(5, $stream->tokens);

        self::assertInstanceOf(TextToken::class, $stream->tokens[0]);
        self::assertSame('Hello ', $stream->tokens[0]->op1);

        self::assertInstanceOf(IfOpenToken::class, $stream->tokens[1]);
        self::assertSame('user.isActive', $stream->tokens[1]->op1);

        self::assertInstanceOf(TextToken::class, $stream->tokens[2]);
        self::assertSame('World', $stream->tokens[2]->op1);

        self::assertInstanceOf(IfCloseToken::class, $stream->tokens[3]);

        self::assertInstanceOf(TextToken::class, $stream->tokens[4]);
        self::assertSame('!', $stream->tokens[4]->op1);
    }
}
