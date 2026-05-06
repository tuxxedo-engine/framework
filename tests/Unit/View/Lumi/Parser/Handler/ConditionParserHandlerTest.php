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
use Tuxxedo\View\Lumi\Parser\Handler\ConditionParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Token\ElseIfToken;
use Tuxxedo\View\Lumi\Syntax\Token\ElseToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndIfToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\IfToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class ConditionParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private ConditionParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ConditionParserHandler();
    }

    public function testParsesIfWithBody(): void
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
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'hello',
                    ),
                    new EndIfToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'user',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'hello',
        );
    }

    public function testParsesIfWithElseBranch(): void
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
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'A',
                    ),
                    new ElseToken(
                        line: 3,
                    ),
                    new TextToken(
                        line: 4,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 5,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 1,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'A',
        );

        $this->assertTextNode(
            node: $nodes[0]->else[0],
            expectedText: 'B',
        );
    }

    public function testParsesIfWithElseIfBranch(): void
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
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'a',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'A',
                    ),
                    new ElseIfToken(
                        line: 3,
                    ),
                    new IdentifierToken(
                        line: 3,
                        op1: 'b',
                    ),
                    new EndToken(
                        line: 3,
                    ),
                    new TextToken(
                        line: 4,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 5,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 1,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertConditionalBranchNode(
            node: $nodes[0]->branches[0],
            expectedBodyCount: 1,
        );

        $this->assertIdentifierNode(
            node: $nodes[0]->branches[0]->operand,
            expectedName: 'b',
        );

        $this->assertTextNode(
            node: $nodes[0]->branches[0]->body[0],
            expectedText: 'B',
        );
    }

    public function testParsesNestedIfBlocks(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'a',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new IfToken(
                        line: 2,
                    ),
                    new IdentifierToken(
                        line: 2,
                        op1: 'b',
                    ),
                    new EndToken(
                        line: 2,
                    ),
                    new TextToken(
                        line: 3,
                        op1: 'inner',
                    ),
                    new EndIfToken(
                        line: 4,
                    ),
                    new EndIfToken(
                        line: 5,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertConditionalNode(
            node: $nodes[0]->body[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
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
                    new IfToken(
                        line: 1,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new EndIfToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }
}
