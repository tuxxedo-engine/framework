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
use Tuxxedo\Http\Response\PrefersHeadersInterface;
use Tuxxedo\Http\Response\Stream\SseEvent;
use Tuxxedo\Http\Response\Stream\SseEventInterface;
use Tuxxedo\Http\Response\Stream\SseStreamProxy;
use Tuxxedo\Http\Response\Stream\StreamProxyInterface;

class SseStreamProxyTest extends TestCase
{
    /**
     * @param \Closure(): \Generator<SseEventInterface>|\Generator<SseEventInterface> $generator
     */
    private static function makeProxy(
        \Closure|\Generator $generator,
    ): StreamProxyInterface {
        return new SseStreamProxy(
            generator: $generator,
        );
    }

    public function testConstructorWithClosure(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'hello',
                );
            },
        );

        self::assertSame("data: hello\n\n", $proxy->read());
    }

    public function testConstructorWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield SseEvent::create(
                data: 'hello',
            );
        })();

        $proxy = new SseStreamProxy(
            generator: $generator,
        );

        self::assertSame("data: hello\n\n", $proxy->read());
    }

    public function testImplementsPrefersHeadersInterface(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertInstanceOf(PrefersHeadersInterface::class, $proxy);
    }

    public function testHeadersExposeContentTypeAndCacheControl(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertCount(2, $proxy->headers);
        self::assertSame('Content-Type', $proxy->headers[0]->name);
        self::assertSame('text/event-stream', $proxy->headers[0]->value);
        self::assertSame('Cache-Control', $proxy->headers[1]->name);
        self::assertSame('no-cache', $proxy->headers[1]->value);
    }

    public function testEofFalseInitiallyWhenGeneratorHasEvents(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'hello',
                );
            },
        );

        self::assertFalse($proxy->eof());
    }

    public function testEofTrueAfterReadingFinalEvent(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'hello',
                );
            },
        );

        $proxy->read();

        self::assertTrue($proxy->eof());
    }

    public function testEofTrueOnEmptyGenerator(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        $proxy->read();

        self::assertTrue($proxy->eof());
    }

    public function testReadReturnsEventsInOrder(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'first',
                );

                yield SseEvent::create(
                    data: 'second',
                );
            },
        );

        self::assertSame("data: first\n\n", $proxy->read());
        self::assertSame("data: second\n\n", $proxy->read());
    }

    public function testReadReturnsNullAfterExhausted(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'hello',
                );
            },
        );

        $proxy->read();

        self::assertNull($proxy->read());
    }

    public function testReadReturnsNullOnEmptyGenerator(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertNull($proxy->read());
    }

    public function testGetSizeAlwaysReturnsNull(): void
    {
        $proxy = self::makeProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertNull($proxy->getSize());
    }

    public function testReadFormatsEventWithId(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'payload',
                    id: 'evt-1',
                );
            },
        );

        self::assertSame("id: evt-1\ndata: payload\n\n", $proxy->read());
    }

    public function testReadFormatsEventWithEventName(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'payload',
                    event: 'update',
                );
            },
        );

        self::assertSame("event: update\ndata: payload\n\n", $proxy->read());
    }

    public function testReadFormatsEventWithRetry(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'payload',
                    retry: 5000,
                );
            },
        );

        self::assertSame("retry: 5000\ndata: payload\n\n", $proxy->read());
    }

    public function testReadFormatsEventWithAllFields(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'payload',
                    id: 'evt-1',
                    event: 'update',
                    retry: 5000,
                );
            },
        );

        self::assertSame(
            "id: evt-1\nevent: update\nretry: 5000\ndata: payload\n\n",
            $proxy->read(),
        );
    }

    public function testReadFormatsCommentEventInsteadOfData(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::comment(
                    comment: 'debug-marker',
                );
            },
        );

        self::assertSame(": debug-marker\n\n", $proxy->read());
    }

    public function testReadFormatsKeepaliveAsComment(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::keepalive();
            },
        );

        self::assertSame(": keepalive\n\n", $proxy->read());
    }

    public function testContentsReturnsConcatenatedEvents(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'first',
                );

                yield SseEvent::create(
                    data: 'second',
                );
            },
        );

        self::assertSame("data: first\n\ndata: second\n\n", $proxy->contents());
    }

    public function testContentsOnEmptyGeneratorReturnsEmptyString(): void
    {
        $proxy = new SseStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertSame('', $proxy->contents());
    }
}
