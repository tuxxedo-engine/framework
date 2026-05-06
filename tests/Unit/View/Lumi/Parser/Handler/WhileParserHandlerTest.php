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

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\WhileParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndWhileToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\WhileToken;

class WhileParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private WhileParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new WhileParserHandler();
    }

    public function testParsesWhileWithBody(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new WhileToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'running',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'inside',
                    ),
                    new EndWhileToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertWhileNode(
            node: $nodes[0],
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(WhileNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'running',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'inside',
        );
    }

    public function testThrowsOnEmptyConditionExpression(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new WhileToken(
                        line: 1,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new EndWhileToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }
}
