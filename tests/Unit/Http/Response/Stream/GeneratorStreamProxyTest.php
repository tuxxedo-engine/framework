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

namespace Unit\Http\Response\Stream;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Response\Stream\GeneratorStreamProxy;
use Tuxxedo\Http\Response\Stream\StreamProxyInterface;

class GeneratorStreamProxyTest extends TestCase
{
    /**
     * @param \Closure(): \Generator<string>|\Generator<string> $generator
     */
    private static function makeProxy(
        \Closure|\Generator $generator,
    ): StreamProxyInterface {
        return new GeneratorStreamProxy(
            generator: $generator,
        );
    }

    public function testConstructorWithClosure(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield 'hello';
            },
        );

        self::assertSame('hello', $proxy->read());
    }

    public function testConstructorWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'hello';
        })();

        $proxy = new GeneratorStreamProxy($generator);

        self::assertSame('hello', $proxy->read());
    }

    public function testEofFalseWhenNotExhausted(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield 'hello';
            },
        );

        self::assertFalse($proxy->eof());
    }

    public function testEofTrueOnEmptyGenerator(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield from [];
            },
        );

        self::assertTrue($proxy->eof());
    }

    public function testEofTrueAfterExhausted(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield 'hello';
            },
        );

        $proxy->read();

        self::assertTrue($proxy->eof());
    }

    public function testGetSizeAlwaysReturnsNull(): void
    {
        $proxy = self::makeProxy(
            static function (): \Generator {
                yield 'hello';
            },
        );

        self::assertNull($proxy->getSize());
    }

    public function testReadReturnsChunksInOrder(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield 'foo';
                yield 'bar';
                yield 'baz';
            },
        );

        self::assertSame('foo', $proxy->read());
        self::assertSame('bar', $proxy->read());
        self::assertSame('baz', $proxy->read());
    }

    public function testReadReturnsNullWhenExhausted(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield 'hello';
            },
        );

        $proxy->read();

        self::assertNull($proxy->read());
    }

    public function testContentsReturnsConcatenatedChunks(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield 'foo';
                yield 'bar';
                yield 'baz';
            },
        );

        self::assertSame('foobarbaz', $proxy->contents());
    }

    public function testContentsOnEmptyGeneratorReturnsEmptyString(): void
    {
        $proxy = new GeneratorStreamProxy(
            static function (): \Generator {
                yield from [];
            },
        );

        self::assertSame('', $proxy->contents());
    }
}
