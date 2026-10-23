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
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Parser\Handler\DoWhileParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\WhileParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Token\DoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndWhileToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;
use Tuxxedo\View\Lumi\Syntax\Token\WhileToken;

class WhileParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private WhileParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new WhileParserHandler();
    }

    /**
     * @return TokenInterface[]
     */
    private function header(
        string $condition,
        int $line,
    ): array {
        return [
            new WhileToken(
                line: $line,
            ),
            new IdentifierToken(
                line: $line,
                op1: $condition,
            ),
            new EndToken(
                line: $line,
            ),
        ];
    }

    private function text(
        string $text,
        int $line,
    ): TextToken {
        return new TextToken(
            line: $line,
            op1: $text,
        );
    }

    private function nestingParser(): Parser
    {
        return Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new WhileParserHandler(),
                new DoWhileParserHandler(),
            ],
        );
    }

    public function testParsesNestedWhileLoop(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    ...$this->header(
                        condition: 'b',
                        line: 2,
                    ),
                    $this->text(
                        text: 'inner',
                        line: 3,
                    ),
                    new EndWhileToken(
                        line: 4,
                    ),
                    new EndWhileToken(
                        line: 5,
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

        $this->assertWhileNode(
            node: $nodes[0]->body[0],
            expectedBodyCount: 1,
        );
    }

    public function testParsesThreeLevelNestedWhileLoop(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    ...$this->header(
                        condition: 'b',
                        line: 2,
                    ),
                    ...$this->header(
                        condition: 'c',
                        line: 3,
                    ),
                    $this->text(
                        text: 'innermost',
                        line: 4,
                    ),
                    new EndWhileToken(
                        line: 5,
                    ),
                    $this->text(
                        text: 'middle',
                        line: 6,
                    ),
                    new EndWhileToken(
                        line: 7,
                    ),
                    $this->text(
                        text: 'outer',
                        line: 8,
                    ),
                    new EndWhileToken(
                        line: 9,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $outer = $nodes[0];

        $this->assertWhileNode(
            node: $outer,
            expectedBodyCount: 2,
        );

        self::assertInstanceOf(WhileNode::class, $outer);

        $middle = $outer->body[0];

        $this->assertWhileNode(
            node: $middle,
            expectedBodyCount: 2,
        );

        self::assertInstanceOf(WhileNode::class, $middle);

        $this->assertWhileNode(
            node: $middle->body[0],
            expectedBodyCount: 1,
        );

        $this->assertTextNode(
            node: $middle->body[1],
            expectedText: 'middle',
        );

        $this->assertTextNode(
            node: $outer->body[1],
            expectedText: 'outer',
        );
    }

    public function testParsesSiblingNestedWhileLoops(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    ...$this->header(
                        condition: 'b',
                        line: 2,
                    ),
                    new EndWhileToken(
                        line: 3,
                    ),
                    ...$this->header(
                        condition: 'c',
                        line: 4,
                    ),
                    new EndWhileToken(
                        line: 5,
                    ),
                    $this->text(
                        text: 'tail',
                        line: 6,
                    ),
                    new EndWhileToken(
                        line: 7,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertWhileNode(
            node: $nodes[0],
            expectedBodyCount: 3,
        );

        self::assertInstanceOf(WhileNode::class, $nodes[0]);

        $this->assertWhileNode(
            node: $nodes[0]->body[0],
            expectedBodyCount: 0,
        );

        $this->assertWhileNode(
            node: $nodes[0]->body[1],
            expectedBodyCount: 0,
        );

        $this->assertTextNode(
            node: $nodes[0]->body[2],
            expectedText: 'tail',
        );
    }

    public function testParsesDoWhileInsideWhile(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    new DoToken(
                        line: 2,
                    ),
                    $this->text(
                        text: 'inner',
                        line: 3,
                    ),
                    ...$this->header(
                        condition: 'b',
                        line: 4,
                    ),
                    new EndWhileToken(
                        line: 5,
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

        $this->assertDoWhileNode(
            node: $nodes[0]->body[0],
            expectedBodyCount: 1,
        );
    }

    public function testAmbiguousWhileInsideDoIsReadAsLoopHeader(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    new DoToken(
                        line: 2,
                    ),
                    ...$this->header(
                        condition: 'x',
                        line: 3,
                    ),
                    new EndWhileToken(
                        line: 4,
                    ),
                    ...$this->header(
                        condition: 'y',
                        line: 5,
                    ),
                    new EndWhileToken(
                        line: 6,
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

        $doWhile = $nodes[0]->body[0];

        $this->assertDoWhileNode(
            node: $doWhile,
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(DoWhileNode::class, $doWhile);

        $this->assertIdentifierNode(
            node: $doWhile->operand,
            expectedName: 'y',
        );

        $this->assertWhileNode(
            node: $doWhile->body[0],
            expectedBodyCount: 0,
        );
    }

    public function testParsesDoWhileFollowedBySiblingWhileInsideWhile(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    new DoToken(
                        line: 2,
                    ),
                    ...$this->header(
                        condition: 'b',
                        line: 3,
                    ),
                    ...$this->header(
                        condition: 'c',
                        line: 4,
                    ),
                    new EndWhileToken(
                        line: 5,
                    ),
                    new EndWhileToken(
                        line: 6,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertWhileNode(
            node: $nodes[0],
            expectedBodyCount: 2,
        );

        self::assertInstanceOf(WhileNode::class, $nodes[0]);

        $doWhile = $nodes[0]->body[0];

        $this->assertDoWhileNode(
            node: $doWhile,
            expectedBodyCount: 0,
        );

        self::assertInstanceOf(DoWhileNode::class, $doWhile);

        $this->assertIdentifierNode(
            node: $doWhile->operand,
            expectedName: 'b',
        );

        $this->assertWhileNode(
            node: $nodes[0]->body[1],
            expectedBodyCount: 0,
        );
    }

    public function testNestedWhileLoopLeavesFollowingTokensInStream(): void
    {
        $stream = new TokenStream(
            tokens: [
                ...$this->header(
                    condition: 'a',
                    line: 1,
                ),
                ...$this->header(
                    condition: 'b',
                    line: 2,
                ),
                new EndWhileToken(
                    line: 3,
                ),
                new EndWhileToken(
                    line: 4,
                ),
                $this->text(
                    text: 'after',
                    line: 5,
                ),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: $stream,
        );

        self::assertCount(1, $nodes);
        self::assertFalse($stream->eof());
        self::assertSame('after', $stream->current()->op1);
    }

    public function testThrowsOnUnterminatedOuterWhileLoopAroundNestedLoop(): void
    {
        self::expectException(LexerException::class);

        $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    ...$this->header(
                        condition: 'b',
                        line: 2,
                    ),
                    new EndWhileToken(
                        line: 3,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnUnclosedDoInsideWhile(): void
    {
        self::expectException(LexerException::class);

        $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    new DoToken(
                        line: 2,
                    ),
                    new EndWhileToken(
                        line: 3,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnUnterminatedWhileLoop(): void
    {
        self::expectException(LexerException::class);

        $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        condition: 'a',
                        line: 1,
                    ),
                    $this->text(
                        text: 'body',
                        line: 2,
                    ),
                ],
            ),
        );
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
