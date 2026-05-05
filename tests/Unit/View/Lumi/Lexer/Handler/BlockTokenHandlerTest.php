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

use Fixture\View\Lumi\Lexer\Handler\BlockTokenHandler\SpyBlockHandler;
use Fixture\View\Lumi\Lexer\Handler\BlockTokenHandler\SpyExpressionSeparatorOptionalBlockHandler;
use Fixture\View\Lumi\Lexer\Handler\BlockTokenHandler\SpyExpressiveBlockHandler;
use Fixture\View\Lumi\Lexer\Handler\BlockTokenHandler\SpyStandaloneBlockHandler;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\Handler\BlockTokenHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;
use Tuxxedo\View\Lumi\Lexer\LexerStateFlag;

class BlockTokenHandlerTest extends TestCase
{
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testCreateDefaultHandlersReturnsBlockHandlerInstances(): void
    {
        $handlers = BlockTokenHandler::createDefaultHandlers();

        self::assertNotEmpty($handlers);

        foreach ($handlers as $handler) {
            self::assertInstanceOf(BlockHandlerInterface::class, $handler);
        }
    }

    public function testCreateWithDefaultHandlersConstructsTokenHandler(): void
    {
        $handler = BlockTokenHandler::createWithDefaultHandlers();

        self::assertInstanceOf(BlockTokenHandler::class, $handler);
    }

    public function testCreateWithDefaultHandlersAcceptsAdditionalHandlers(): void
    {
        $handler = BlockTokenHandler::createWithDefaultHandlers(
            handlers: [
                new SpyBlockHandler('custom'),
            ],
        );

        self::assertInstanceOf(BlockTokenHandler::class, $handler);
    }

    public function testCreateWithoutDefaultHandlersConstructsTokenHandler(): void
    {
        $handler = BlockTokenHandler::createWithoutDefaultHandlers();

        self::assertInstanceOf(BlockTokenHandler::class, $handler);
    }

    public function testGetStartingSequenceReturnsBlockOpen(): void
    {
        self::assertSame(
            '{%',
            BlockTokenHandler::createWithoutDefaultHandlers()->getStartingSequence(),
        );
    }

    public function testGetEndingSequenceReturnsBlockClose(): void
    {
        self::assertSame(
            '%}',
            BlockTokenHandler::createWithoutDefaultHandlers()->getEndingSequence(),
        );
    }

    public function testTokenizeDispatchesStandaloneDirectiveWithStandaloneState(): void
    {
        $spy = new SpyStandaloneBlockHandler('else');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $tokens = $handler->tokenize(
            startingLine: 1,
            buffer: 'else',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame([], $tokens);
        self::assertSame(1, $spy->callCount);
        self::assertSame(1, $spy->lastStartingLine);
        self::assertSame('', $spy->lastExpression);
        self::assertSame(BlockHandlerState::STANDALONE, $spy->lastBlockState);
    }

    public function testTokenizeDispatchesExpressiveDirectiveWithExpressiveState(): void
    {
        $spy = new SpyExpressiveBlockHandler('if');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $handler->tokenize(
            startingLine: 1,
            buffer: 'if condition',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame(1, $spy->callCount);
        self::assertSame('condition', $spy->lastExpression);
        self::assertSame(BlockHandlerState::EXPRESSIVE, $spy->lastBlockState);
    }

    public function testTokenizeLowercasesDirectiveBeforeDispatch(): void
    {
        $spy = new SpyExpressiveBlockHandler('if');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $handler->tokenize(
            startingLine: 1,
            buffer: 'IF condition',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame(1, $spy->callCount);
        self::assertSame('condition', $spy->lastExpression);
    }

    public function testTokenizeTrimsSurroundingWhitespaceFromBuffer(): void
    {
        $spy = new SpyExpressiveBlockHandler('if');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $handler->tokenize(
            startingLine: 1,
            buffer: '   if condition   ',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame('condition', $spy->lastExpression);
    }

    public function testTokenizeDispatchesFlexibleDirectiveAsStandaloneWhenNoExpression(): void
    {
        $spy = new SpyBlockHandler('break');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $handler->tokenize(
            startingLine: 1,
            buffer: 'break',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame(1, $spy->callCount);
        self::assertSame('', $spy->lastExpression);
        self::assertSame(BlockHandlerState::STANDALONE, $spy->lastBlockState);
    }

    public function testTokenizeDispatchesFlexibleDirectiveAsExpressiveWhenExpressionPresent(): void
    {
        $spy = new SpyBlockHandler('break');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $handler->tokenize(
            startingLine: 1,
            buffer: 'break 2',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame(1, $spy->callCount);
        self::assertSame('2', $spy->lastExpression);
        self::assertSame(BlockHandlerState::EXPRESSIVE, $spy->lastBlockState);
    }

    public function testTokenizePropagatesStartingLineToHandler(): void
    {
        $spy = new SpyExpressiveBlockHandler('if');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $handler->tokenize(
            startingLine: 42,
            buffer: 'if x',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame(42, $spy->lastStartingLine);
    }

    public function testTokenizeThrowsOnEmptyBuffer(): void
    {
        $handler = BlockTokenHandler::createWithoutDefaultHandlers();

        $this->expectException(LexerException::class);

        $handler->tokenize(
            startingLine: 1,
            buffer: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );
    }

    public function testTokenizeThrowsOnDirectiveStartingWithDigit(): void
    {
        $handler = BlockTokenHandler::createWithoutDefaultHandlers();

        $this->expectException(LexerException::class);

        $handler->tokenize(
            startingLine: 1,
            buffer: '123abc',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );
    }

    public function testTokenizeThrowsOnUnknownDirective(): void
    {
        $handler = BlockTokenHandler::createWithoutDefaultHandlers();

        $this->expectException(LexerException::class);

        $handler->tokenize(
            startingLine: 1,
            buffer: 'unknown',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );
    }

    public function testTokenizeThrowsWhenStandaloneDirectiveHasExpression(): void
    {
        $spy = new SpyStandaloneBlockHandler('else');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $this->expectException(LexerException::class);

        $handler->tokenize(
            startingLine: 1,
            buffer: 'else extra',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );
    }

    public function testTokenizeThrowsWhenExpressiveDirectiveHasNoExpression(): void
    {
        $spy = new SpyExpressiveBlockHandler('if');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $this->expectException(LexerException::class);

        $handler->tokenize(
            startingLine: 1,
            buffer: 'if',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );
    }

    public function testTokenizeThrowsWhenParenthesizedExpressionUsedOnNonOptionalSeparatorHandler(): void
    {
        $spy = new SpyExpressiveBlockHandler('if');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $this->expectException(LexerException::class);

        $handler->tokenize(
            startingLine: 1,
            buffer: 'if(condition)',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );
    }

    public function testTokenizeAcceptsParenthesizedExpressionForExpressionSeparatorOptionalHandler(): void
    {
        $spy = new SpyExpressionSeparatorOptionalBlockHandler('include');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $handler->tokenize(
            startingLine: 1,
            buffer: 'include(file)',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame(1, $spy->callCount);
        self::assertSame('(file)', $spy->lastExpression);
    }

    public function testTokenizeReturnsEmptyArrayInRawModeForNonMatchingDirective(): void
    {
        $spy = new SpyBlockHandler('foo');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setTextAsRawEndDirective('endraw');

        $tokens = $handler->tokenize(
            startingLine: 1,
            buffer: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame([], $tokens);
        self::assertSame(0, $spy->callCount);
    }

    public function testTokenizeDispatchesInRawModeWhenDirectiveMatchesEndDirective(): void
    {
        $spy = new SpyStandaloneBlockHandler('endraw');
        $handler = BlockTokenHandler::createWithoutDefaultHandlers(
            handlers: [
                $spy,
            ],
        );

        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setTextAsRawEndDirective('endraw');

        $handler->tokenize(
            startingLine: 1,
            buffer: 'endraw',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertSame(1, $spy->callCount);
    }
}
