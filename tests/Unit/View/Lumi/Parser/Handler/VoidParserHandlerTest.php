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

namespace Unit\View\Lumi\Parser\Handler;

use Fixture\View\Lumi\Parser\Parser\BarHandler;
use Fixture\View\Lumi\Parser\Parser\BarToken;
use Fixture\View\Lumi\Parser\Parser\EndTokenStubHandler;
use Fixture\View\Lumi\Parser\Parser\FooToken;
use Fixture\View\Lumi\Parser\Parser\IdentifierTokenStubHandler;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Parser\Handler\VoidParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;

class VoidParserHandlerTest extends TestCase
{
    public function testConstructorAcceptsTokenClassName(): void
    {
        $handler = new VoidParserHandler(
            tokenClassName: FooToken::class,
        );

        self::assertSame(
            FooToken::class,
            $handler->tokenClassName,
        );
    }

    public function testThrowsParserExceptionWhenInvoked(): void
    {
        $handler = new VoidParserHandler(
            tokenClassName: FooToken::class,
        );

        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                $handler,
            ],
        );

        self::expectException(ParserException::class);

        $handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new FooToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testExpectedListIncludesOtherPlainHandlerTokens(): void
    {
        $handler = new VoidParserHandler(
            tokenClassName: FooToken::class,
        );

        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                $handler,
                new BarHandler(),
            ],
        );

        try {
            $handler->parse(
                parser: $parser,
                stream: new TokenStream(
                    tokens: [
                        new FooToken(
                            line: 1,
                        ),
                    ],
                ),
            );
        } catch (ParserException $e) {
            self::assertStringContainsString(BarToken::class, $e->getMessage());
        }
    }

    public function testExpectedListExcludesExpressionTokenHandlers(): void
    {
        $handler = new VoidParserHandler(
            tokenClassName: FooToken::class,
        );

        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                $handler,
                new IdentifierTokenStubHandler(),
            ],
        );

        try {
            $handler->parse(
                parser: $parser,
                stream: new TokenStream(
                    tokens: [
                        new FooToken(
                            line: 1,
                        ),
                    ],
                ),
            );
        } catch (ParserException $e) {
            self::assertStringNotContainsString(IdentifierToken::class, $e->getMessage());
        }
    }

    public function testExpectedListExcludesVirtualTokenHandlers(): void
    {
        $handler = new VoidParserHandler(
            tokenClassName: FooToken::class,
        );

        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                $handler,
                new EndTokenStubHandler(),
            ],
        );

        try {
            $handler->parse(
                parser: $parser,
                stream: new TokenStream(
                    tokens: [
                        new FooToken(
                            line: 1,
                        ),
                    ],
                ),
            );
        } catch (ParserException $e) {
            self::assertStringNotContainsString(EndToken::class, $e->getMessage());
        }
    }

    public function testExpectedListExcludesOwnTokenClass(): void
    {
        $handler = new VoidParserHandler(
            tokenClassName: FooToken::class,
        );

        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                $handler,
                new BarHandler(),
            ],
        );

        try {
            $handler->parse(
                parser: $parser,
                stream: new TokenStream(
                    tokens: [
                        new FooToken(
                            line: 1,
                        ),
                    ],
                ),
            );
        } catch (ParserException $e) {
            self::assertStringNotContainsString(
                'expected one of "' . FooToken::class,
                $e->getMessage(),
            );
        }
    }
}
