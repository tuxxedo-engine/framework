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

namespace Unit\Http\Response;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\Response\Stream\Stream;
use Tuxxedo\Http\Response\Stream\StreamProxyInterface;

class ResponseEmitterTest extends TestCase
{
    public function testIsSentReturnsFalseInitially(): void
    {
        self::assertFalse((new ResponseEmitter())->sent);
    }

    public function testEmitSetsSent(): void
    {
        $emitter = new ResponseEmitter();

        \ob_start();
        $emitter->emit(response: new Response());
        \ob_end_clean();

        self::assertTrue($emitter->sent);
    }

    public function testEmitWithSendHeadersFalseDoesNotSetSent(): void
    {
        $emitter = new ResponseEmitter();

        \ob_start();
        $emitter->emit(
            response: new Response(
                body: 'hello',
            ),
            sendHeaders: false,
        );
        \ob_end_clean();

        self::assertFalse($emitter->sent);
    }

    public function testEmitOutputsStringBody(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: new Response(
                body: 'hello world',
            ),
            sendHeaders: false,
        );

        self::assertSame('hello world', \ob_get_clean());
    }

    public function testEmitOutputsEmptyBody(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: new Response(),
            sendHeaders: false,
        );

        self::assertSame('', \ob_get_clean());
    }

    public function testEmitWithResponseExceptionConvertsToResponse(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: HttpException::fromNotFound(),
            sendHeaders: false,
        );

        self::assertSame('', \ob_get_clean());
    }

    public function testEmitDoesNotResendHeadersOnSubsequentCall(): void
    {
        $emitter = new ResponseEmitter();

        \ob_start();
        $emitter->emit(response: new Response());
        \ob_end_clean();

        \ob_start();
        $emitter->emit(
            response: new Response(
                body: 'second',
            ),
        );

        self::assertSame('second', \ob_get_clean());
        self::assertTrue($emitter->sent);
    }

    public function testEmitWithPlainHeaderSendsHeaders(): void
    {
        $emitter = new ResponseEmitter();

        \ob_start();
        $emitter->emit(
            response: new Response(
                headers: [
                    new Header('X-Custom', 'value'),
                ],
            ),
        );
        \ob_end_clean();

        self::assertTrue($emitter->sent);
    }

    public function testEmitWithCookieHeader(): void
    {
        $emitter = new ResponseEmitter();

        \ob_start();
        $emitter->emit(
            response: new Response(
                headers: [
                    new Cookie(
                        name: 'session',
                        value: 'abc',
                        expires: 0,
                    ),
                ],
            ),
        );
        \ob_end_clean();

        self::assertTrue($emitter->sent);
    }

    public function testEmitOutputsStreamBody(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: Response::stream(
                stream: static function (): \Generator {
                    yield 'hello ';
                    yield 'world';
                },
            ),
            sendHeaders: false,
        );

        self::assertSame('hello world', \ob_get_clean());
    }

    public function testEmitStreamWithAutoFlush(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: Response::stream(
                stream: static function (): \Generator {
                    yield 'chunk';
                },
                autoFlush: true,
            ),
            sendHeaders: false,
        );

        self::assertSame('chunk', \ob_get_clean());
    }

    public function testEmitStreamWithKnownSize(): void
    {
        $resource = \fopen('php://memory', 'r+b');

        self::assertIsResource($resource);

        \fwrite($resource, 'hello');
        \rewind($resource);

        $emitter = new ResponseEmitter();

        \ob_start();
        $emitter->emit(
            response: Response::stream($resource),
        );

        self::assertSame('hello', \ob_get_clean());
    }

    public function testEmitStreamNullChunkBreaksLoop(): void
    {
        $reads = 0;

        $proxy = new class ($reads) implements StreamProxyInterface {
            public function __construct(
                private int &$reads,
            ) {
            }

            public function eof(): bool
            {
                return false;
            }

            public function getSize(): ?int
            {
                return null;
            }

            public function read(): ?string
            {
                $this->reads++;

                return $this->reads === 1 ? 'hello' : null;
            }

            public function contents(): string
            {
                return 'hello';
            }
        };

        \ob_start();

        (new ResponseEmitter())->emit(
            response: new Response(
                body: new Stream($proxy),
            ),
            sendHeaders: false,
        );

        self::assertSame('hello', \ob_get_clean());
    }

    public function testEmitTruncatesStringBodyToContentLength(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: new Response(
                body: 'hello world',
                headers: [
                    new Header('Content-Length', '5'),
                ],
            ),
        );

        self::assertSame('hello', \ob_get_clean());
    }

    public function testEmitTruncatesStringBodyToContentLengthWithMixedHeaderName(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: new Response(
                body: 'hello world',
                headers: [
                    new Header('cOnTeNt-LeNgTh', '5'),
                ],
            ),
        );

        self::assertSame('hello', \ob_get_clean());
    }

    public function testEmitTruncatesStreamBodyToContentLength(): void
    {
        \ob_start();

        (new ResponseEmitter())->emit(
            response: new Response(
                body: Stream::fromGenerator(
                    generator: static function (): \Generator {
                        yield 'hel';
                        yield 'lo';
                        yield 'world';
                    },
                    autoFlush: false,
                ),
                headers: [
                    new Header('Content-Length', '5'),
                ],
            ),
        );

        self::assertSame('hello', \ob_get_clean());
    }
}
