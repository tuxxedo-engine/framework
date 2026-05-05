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

namespace Unit\View\Lumi\Parser;

use Fixture\View\Lumi\Parser\Parser\BarHandler;
use Fixture\View\Lumi\Parser\Parser\BarNode;
use Fixture\View\Lumi\Parser\Parser\BarToken;
use Fixture\View\Lumi\Parser\Parser\FooHandler;
use Fixture\View\Lumi\Parser\Parser\FooNode;
use Fixture\View\Lumi\Parser\Parser\FooToken;
use Fixture\View\Lumi\Parser\Parser\MultiNodeHandler;
use Fixture\View\Lumi\Parser\Parser\StateLeakingHandler;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Parser\Expression\ExpressionParser;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserState;

class ParserTest extends TestCase
{
    public function testCreateDefaultExpressionParserReturnsExpressionParser(): void
    {
        self::assertInstanceOf(
            ExpressionParser::class,
            Parser::createDefaultExpressionParser(),
        );
    }

    public function testCreateDefaultParserStateReturnsParserState(): void
    {
        self::assertInstanceOf(
            ParserState::class,
            Parser::createDefaultParserState(),
        );
    }

    public function testCreateWithoutDefaultHandlersExposesProvidedDependencies(): void
    {
        $expressionParser = new ExpressionParser();
        $state = new ParserState();

        $parser = Parser::createWithoutDefaultHandlers(
            expressionParser: $expressionParser,
            state: $state,
        );

        self::assertSame($expressionParser, $parser->expressionParser);
        self::assertSame($state, $parser->state);
    }

    public function testCreateWithoutDefaultHandlersFallsBackToDefaults(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::assertInstanceOf(ExpressionParser::class, $parser->expressionParser);
        self::assertInstanceOf(ParserState::class, $parser->state);
    }

    public function testCreateWithoutDefaultHandlersStartsWithEmptyHandlerMap(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::assertSame([], $parser->handlers);
    }

    public function testHandlerIsKeyedByTokenClass(): void
    {
        $fooHandler = new FooHandler();

        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                $fooHandler,
            ],
        );

        self::assertArrayHasKey(FooToken::class, $parser->handlers);
        self::assertSame($fooHandler, $parser->handlers[FooToken::class]);
    }

    public function testEmptyTokenStreamProducesEmptyNodeStream(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        $nodes = $parser->parse(
            stream: new TokenStream(
                tokens: [],
            ),
        );

        self::assertSame([], $nodes->nodes);
    }

    public function testSingleTokenIsDispatchedToMatchingHandler(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new FooHandler(),
            ],
        );

        $nodes = $parser->parse(
            stream: new TokenStream(
                tokens: [
                    new FooToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes->nodes);
        self::assertInstanceOf(FooNode::class, $nodes->nodes[0]);
    }

    public function testMultipleTokensAreDispatchedInOrder(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new FooHandler(),
                new BarHandler(),
            ],
        );

        $nodes = $parser->parse(
            stream: new TokenStream(
                tokens: [
                    new FooToken(
                        line: 1,
                    ),
                    new BarToken(
                        line: 2,
                    ),
                    new FooToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(3, $nodes->nodes);
        self::assertInstanceOf(FooNode::class, $nodes->nodes[0]);
        self::assertInstanceOf(BarNode::class, $nodes->nodes[1]);
        self::assertInstanceOf(FooNode::class, $nodes->nodes[2]);
    }

    public function testHandlerCanReturnMultipleNodes(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new MultiNodeHandler(),
            ],
        );

        $nodes = $parser->parse(
            stream: new TokenStream(
                tokens: [
                    new BarToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(3, $nodes->nodes);
        self::assertInstanceOf(FooNode::class, $nodes->nodes[0]);
        self::assertInstanceOf(BarNode::class, $nodes->nodes[1]);
        self::assertInstanceOf(FooNode::class, $nodes->nodes[2]);
    }

    public function testUnknownTokenThrowsParserException(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $parser->parse(
            stream: new TokenStream(
                tokens: [
                    new FooToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testHandlerStateChangesDoNotLeakBetweenInvocations(): void
    {
        $state = new ParserState();

        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new StateLeakingHandler(),
            ],
            state: $state,
        );

        $parser->parse(
            stream: new TokenStream(
                tokens: [
                    new FooToken(
                        line: 1,
                    ),
                    new FooToken(
                        line: 2,
                    ),
                ],
            ),
        );

        self::assertFalse($state->has(StateLeakingHandler::LEAKED_KEY));
        self::assertSame([], $state->stateStack);
    }
}
