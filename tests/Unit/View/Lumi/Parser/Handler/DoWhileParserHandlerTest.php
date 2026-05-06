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
use Tuxxedo\View\Lumi\Parser\Handler\DoWhileParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\WhileParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Token\DoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndWhileToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\WhileToken;

class DoWhileParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private DoWhileParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new DoWhileParserHandler();
    }

    public function testParsesDoWhileWithBody(): void
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
                    new DoToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'inside',
                    ),
                    new WhileToken(
                        line: 3,
                    ),
                    new IdentifierToken(
                        line: 3,
                        op1: 'running',
                    ),
                    new EndToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertDoWhileNode(
            node: $nodes[0],
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(DoWhileNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'running',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'inside',
        );
    }

    public function testParsesDoWhileWithInnerWhileLoopInBody(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new WhileParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new DoToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'before',
                    ),
                    new WhileToken(
                        line: 3,
                    ),
                    new IdentifierToken(
                        line: 3,
                        op1: 'x',
                    ),
                    new EndToken(
                        line: 3,
                    ),
                    new TextToken(
                        line: 4,
                        op1: 'inner',
                    ),
                    new EndWhileToken(
                        line: 5,
                    ),
                    new TextToken(
                        line: 6,
                        op1: 'after',
                    ),
                    new WhileToken(
                        line: 7,
                    ),
                    new IdentifierToken(
                        line: 7,
                        op1: 'y',
                    ),
                    new EndToken(
                        line: 7,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertDoWhileNode(
            node: $nodes[0],
            expectedBodyCount: 3,
        );

        self::assertInstanceOf(DoWhileNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'y',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'before',
        );

        $this->assertWhileNode(
            node: $nodes[0]->body[1],
            expectedBodyCount: 1,
        );

        $this->assertTextNode(
            node: $nodes[0]->body[2],
            expectedText: 'after',
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
                    new DoToken(
                        line: 1,
                    ),
                    new WhileToken(
                        line: 2,
                    ),
                    new EndToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }
}
