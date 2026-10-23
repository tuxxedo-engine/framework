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
use Tuxxedo\View\Lumi\Parser\Handler\ForParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\ForeachParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForEachToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForEachToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;
use Tuxxedo\View\Lumi\Syntax\Type;

class ForeachParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private ForeachParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ForeachParserHandler();
    }

    /**
     * @return TokenInterface[]
     */
    private function header(
        string $value,
        string $iterable,
        int $line,
    ): array {
        return [
            new ForEachToken(
                line: $line,
                op1: $value,
            ),
            new IdentifierToken(
                line: $line,
                op1: $iterable,
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

    private function end(
        int $line,
    ): EndForEachToken {
        return new EndForEachToken(
            line: $line,
        );
    }

    private function nestingParser(): Parser
    {
        return Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ForParserHandler(),
                new ForeachParserHandler(),
            ],
        );
    }

    public function testParsesNestedForeachLoop(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        value: 'row',
                        iterable: 'rows',
                        line: 1,
                    ),
                    ...$this->header(
                        value: 'cell',
                        iterable: 'row',
                        line: 2,
                    ),
                    $this->text(
                        text: 'inner',
                        line: 3,
                    ),
                    $this->end(4),
                    $this->end(5),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertForNode(
            node: $nodes[0],
            expectedValueName: 'row',
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(ForNode::class, $nodes[0]);

        $this->assertForNode(
            node: $nodes[0]->body[0],
            expectedValueName: 'cell',
            expectedBodyCount: 1,
        );
    }

    public function testParsesThreeLevelNestedForeachLoop(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        value: 'a',
                        iterable: 'as',
                        line: 1,
                    ),
                    ...$this->header(
                        value: 'b',
                        iterable: 'a',
                        line: 2,
                    ),
                    ...$this->header(
                        value: 'c',
                        iterable: 'b',
                        line: 3,
                    ),
                    $this->text(
                        text: 'innermost',
                        line: 4,
                    ),
                    $this->end(5),
                    $this->text(
                        text: 'middle',
                        line: 6,
                    ),
                    $this->end(7),
                    $this->text(
                        text: 'outer',
                        line: 8,
                    ),
                    $this->end(9),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $outer = $nodes[0];

        $this->assertForNode(
            node: $outer,
            expectedValueName: 'a',
            expectedBodyCount: 2,
        );

        self::assertInstanceOf(ForNode::class, $outer);

        $middle = $outer->body[0];

        $this->assertForNode(
            node: $middle,
            expectedValueName: 'b',
            expectedBodyCount: 2,
        );

        self::assertInstanceOf(ForNode::class, $middle);

        $this->assertForNode(
            node: $middle->body[0],
            expectedValueName: 'c',
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

    public function testParsesSiblingNestedForeachLoops(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        value: 'a',
                        iterable: 'as',
                        line: 1,
                    ),
                    ...$this->header(
                        value: 'b',
                        iterable: 'a',
                        line: 2,
                    ),
                    $this->end(3),
                    ...$this->header(
                        value: 'c',
                        iterable: 'a',
                        line: 4,
                    ),
                    $this->end(5),
                    $this->text(
                        text: 'tail',
                        line: 6,
                    ),
                    $this->end(7),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertForNode(
            node: $nodes[0],
            expectedValueName: 'a',
            expectedBodyCount: 3,
        );

        self::assertInstanceOf(ForNode::class, $nodes[0]);

        $this->assertForNode(
            node: $nodes[0]->body[0],
            expectedValueName: 'b',
            expectedBodyCount: 0,
        );

        $this->assertForNode(
            node: $nodes[0]->body[1],
            expectedValueName: 'c',
            expectedBodyCount: 0,
        );

        $this->assertTextNode(
            node: $nodes[0]->body[2],
            expectedText: 'tail',
        );
    }

    public function testParsesSameKindLoopNestedThroughOtherLoopKind(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        value: 'a',
                        iterable: 'as',
                        line: 1,
                    ),
                    new ForToken(
                        line: 2,
                        op1: 'b',
                    ),
                    new IdentifierToken(
                        line: 2,
                        op1: 'a',
                    ),
                    new EndToken(
                        line: 2,
                    ),
                    ...$this->header(
                        value: 'c',
                        iterable: 'b',
                        line: 3,
                    ),
                    $this->text(
                        text: 'inner',
                        line: 4,
                    ),
                    $this->end(5),
                    new EndForToken(
                        line: 6,
                    ),
                    $this->end(7),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertForNode(
            node: $nodes[0],
            expectedValueName: 'a',
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(ForNode::class, $nodes[0]);

        $other = $nodes[0]->body[0];

        $this->assertForNode(
            node: $other,
            expectedValueName: 'b',
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(ForNode::class, $other);

        $this->assertForNode(
            node: $other->body[0],
            expectedValueName: 'c',
            expectedBodyCount: 1,
        );
    }

    public function testNestedForeachLoopLeavesFollowingTokensInStream(): void
    {
        $stream = new TokenStream(
            tokens: [
                ...$this->header(
                    value: 'a',
                    iterable: 'as',
                    line: 1,
                ),
                ...$this->header(
                    value: 'b',
                    iterable: 'a',
                    line: 2,
                ),
                $this->end(3),
                $this->end(4),
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

    public function testThrowsOnUnterminatedOuterForeachLoopAroundNestedLoop(): void
    {
        self::expectException(LexerException::class);

        $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        value: 'a',
                        iterable: 'as',
                        line: 1,
                    ),
                    ...$this->header(
                        value: 'b',
                        iterable: 'a',
                        line: 2,
                    ),
                    $this->end(3),
                ],
            ),
        );
    }

    public function testParsesForeachOverIterable(): void
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
                    new ForEachToken(
                        line: 1,
                        op1: 'item',
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'items',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'inside',
                    ),
                    new EndForEachToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertForNode(
            node: $nodes[0],
            expectedValueName: 'item',
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(ForNode::class, $nodes[0]);
        self::assertInstanceOf(PropertyAccessNode::class, $nodes[0]->iterator);

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'inside',
        );
    }

    public function testParsesForeachWithKey(): void
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
                    new ForEachToken(
                        line: 1,
                        op1: 'value',
                        op2: 'key',
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'items',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'inside',
                    ),
                    new EndForEachToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertForNode(
            node: $nodes[0],
            expectedValueName: 'value',
            expectedBodyCount: 1,
            expectedKeyName: 'key',
        );
    }

    public function testThrowsOnEmptyIteratorExpression(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new ForEachToken(
                        line: 1,
                        op1: 'item',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new EndForEachToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnNonIterableIteratorExpression(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new ForEachToken(
                        line: 1,
                        op1: 'item',
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: '5',
                        op2: Type::INT->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new EndForEachToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }
}
