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

use Fixture\View\Lumi\Parser\NodeStream\BarNode;
use Fixture\View\Lumi\Parser\NodeStream\FooNode;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\ParserException;

class NodeStreamTest extends TestCase
{
    public function testConstructorStoresNodes(): void
    {
        $node = new FooNode();
        $stream = new NodeStream(
            [
                $node,
            ],
        );

        self::assertSame([$node], $stream->nodes);
    }

    public function testInitialPositionIsZero(): void
    {
        $stream = new NodeStream(
            [
                new FooNode(),
            ],
        );

        self::assertSame(0, $stream->position);
    }

    public function testCloneResetsPosition(): void
    {
        $stream = new NodeStream(
            [
                new FooNode(),
                new FooNode(),
            ],
        );

        $stream->consume();

        $cloned = clone $stream;

        self::assertSame(0, $cloned->position);
    }

    public function testEofReturnsTrueOnEmptyStream(): void
    {
        $stream = new NodeStream([]);

        self::assertTrue($stream->eof());
    }

    public function testEofReturnsFalseWithNodes(): void
    {
        $stream = new NodeStream(
            [
                new FooNode(),
            ],
        );

        self::assertFalse($stream->eof());
    }

    public function testEofReturnsTrueAfterConsumingAll(): void
    {
        $stream = new NodeStream(
            [
                new FooNode(),
            ],
        );

        $stream->consume();

        self::assertTrue($stream->eof());
    }

    public function testCurrentReturnsFirstNode(): void
    {
        $node = new FooNode();
        $stream = new NodeStream(
            [
                $node,
            ],
        );

        self::assertSame($node, $stream->current());
    }

    public function testCurrentDoesNotAdvancePosition(): void
    {
        $stream = new NodeStream(
            [
                new FooNode(),
            ],
        );

        $stream->current();

        self::assertSame(0, $stream->position);
    }

    public function testCurrentThrowsAtEof(): void
    {
        $stream = new NodeStream([]);

        self::expectException(ParserException::class);

        $stream->current();
    }

    public function testConsumeReturnsNode(): void
    {
        $node = new FooNode();
        $stream = new NodeStream(
            [
                $node,
            ],
        );

        self::assertSame($node, $stream->consume());
    }

    public function testConsumeAdvancesPosition(): void
    {
        $stream = new NodeStream(
            [
                new FooNode(),
            ],
        );

        $stream->consume();

        self::assertSame(1, $stream->position);
    }

    public function testConsumeReturnsNodesInOrder(): void
    {
        $first = new FooNode();
        $second = new BarNode();
        $stream = new NodeStream(
            [
                $first,
                $second,
            ],
        );

        self::assertSame($first, $stream->consume());
        self::assertSame($second, $stream->consume());
    }

    public function testConsumeThrowsAtEof(): void
    {
        $stream = new NodeStream([]);

        self::expectException(ParserException::class);

        $stream->consume();
    }

    public function testCurrentThrowsAfterConsumingAll(): void
    {
        $stream = new NodeStream(
            [
                new FooNode(),
            ],
        );

        $stream->consume();

        self::expectException(ParserException::class);

        $stream->current();
    }
}
