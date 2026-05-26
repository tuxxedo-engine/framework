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
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\Stream\JsonStreamFormat;
use Tuxxedo\Http\Response\Stream\SseEvent;
use Tuxxedo\Http\Response\Stream\Stream;
use Tuxxedo\Http\Response\Stream\StreamInterface;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\StaticRouter;

class ResponseTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $response = new Response();

        self::assertSame('', $response->body);
        self::assertSame([], $response->headers);
        self::assertSame(ResponseCode::OK, $response->responseCode);
    }

    public function testConstructorExplicit(): void
    {
        $response = new Response(
            body: 'hello',
            headers: [
                new Header('X-Foo', 'bar'),
            ],
            responseCode: ResponseCode::NOT_FOUND,
        );

        self::assertSame('hello', $response->body);
        self::assertCount(1, $response->headers);
        self::assertSame(ResponseCode::NOT_FOUND, $response->responseCode);
    }

    public function testConstructorAcceptsResponseCodeAsInt(): void
    {
        $response = new Response(
            responseCode: 404,
        );

        self::assertSame(ResponseCode::NOT_FOUND, $response->responseCode);
    }

    public function testConstructorAcceptsResponseCodeAsEnum(): void
    {
        $response = new Response(
            responseCode: ResponseCode::CREATED,
        );

        self::assertSame(ResponseCode::CREATED, $response->responseCode);
    }

    public function testConstructorNormalizesIntToMatchingEnumCase(): void
    {
        $fromInt = new Response(
            responseCode: 201,
        );

        $fromEnum = new Response(
            responseCode: ResponseCode::CREATED,
        );

        self::assertSame($fromEnum->responseCode, $fromInt->responseCode);
    }

    public function testConstructorThrowsForUnknownIntResponseCode(): void
    {
        self::expectException(\ValueError::class);

        new Response(
            responseCode: 999,
        );
    }

    public function testToResponseReturnsSelf(): void
    {
        $response = new Response('hello');

        self::assertSame($response, $response->toResponse(new Container()));
    }

    public function testJsonEncodesValue(): void
    {
        $response = Response::json(
            [
                'key' => 'value',
            ],
        );

        self::assertSame('{"key":"value"}', $response->body);
    }

    public function testJsonSetsContentTypeHeader(): void
    {
        $response = Response::json([]);

        self::assertSame('Content-Type', $response->headers[0]->name);
        self::assertSame('application/json', $response->headers[0]->value);
    }

    public function testJsonPrettyPrint(): void
    {
        $response = Response::json(
            json: [
                'key' => 'value',
            ],
            prettyPrint: true,
        );

        self::assertIsString($response->body);
        self::assertStringContainsString("\n", $response->body);
    }

    public function testJsonWithResponseCode(): void
    {
        $response = Response::json(
            json: [],
            responseCode: ResponseCode::CREATED,
        );

        self::assertSame(ResponseCode::CREATED, $response->responseCode);
    }

    public function testJsonInvalidThrowsHttpException(): void
    {
        self::expectException(HttpException::class);

        (void) Response::json(\NAN);
    }

    public function testJsonInvalidHasJsonExceptionAsPrevious(): void
    {
        try {
            (void) Response::json(\NAN);

            self::fail('Expected HttpException to be thrown');
        } catch (HttpException $e) {
            self::assertInstanceOf(\JsonException::class, $e->getPrevious());
        }
    }

    public function testCaptureOutputBecomesBody(): void
    {
        $response = Response::capture(
            static function (): void {
                echo 'hello world';
            },
        );

        self::assertSame('hello world', $response->body);
    }

    public function testCaptureWithResponseCode(): void
    {
        $response = Response::capture(
            callback: static function (): void {
            },
            responseCode: ResponseCode::CREATED,
        );

        self::assertSame(ResponseCode::CREATED, $response->responseCode);
    }

    public function testHtmlSetsBody(): void
    {
        $response = Response::html('<p>hello</p>');

        self::assertSame('<p>hello</p>', $response->body);
    }

    public function testHtmlSetsContentTypeHeader(): void
    {
        $response = Response::html('');

        self::assertSame('Content-Type', $response->headers[0]->name);
        self::assertSame('text/html', $response->headers[0]->value);
    }

    public function testHtmlWithResponseCode(): void
    {
        $response = Response::html(
            html: '',
            responseCode: ResponseCode::CREATED,
        );

        self::assertSame(ResponseCode::CREATED, $response->responseCode);
    }

    public function testTextSetsBody(): void
    {
        $response = Response::text('hello world');

        self::assertSame('hello world', $response->body);
    }

    public function testTextSetsContentTypeHeader(): void
    {
        $response = Response::text('');

        self::assertSame('Content-Type', $response->headers[0]->name);
        self::assertSame('text/plain', $response->headers[0]->value);
    }

    public function testRedirectSetsLocationHeader(): void
    {
        $response = Response::redirect('https://example.com');

        self::assertSame('Location', $response->headers[0]->name);
        self::assertSame('https://example.com', $response->headers[0]->value);
    }

    public function testRedirectDefaultResponseCode(): void
    {
        $response = Response::redirect('https://example.com');

        self::assertSame(ResponseCode::FOUND, $response->responseCode);
    }

    public function testRedirectWithCustomResponseCode(): void
    {
        $response = Response::redirect(
            uri: 'https://example.com',
            responseCode: ResponseCode::MOVED_PERMANENTLY,
        );

        self::assertSame(ResponseCode::MOVED_PERMANENTLY, $response->responseCode);
    }

    public function testEmptyHasEmptyBody(): void
    {
        $response = Response::empty();

        self::assertSame('', $response->body);
        self::assertSame(ResponseCode::OK, $response->responseCode);
    }

    public function testEmptyWithResponseCode(): void
    {
        $response = Response::empty(
            responseCode: ResponseCode::NO_CONTENT,
        );

        self::assertSame(ResponseCode::NO_CONTENT, $response->responseCode);
    }

    public function testStreamWithClosure(): void
    {
        $response = Response::stream(
            stream: static function (): \Generator {
                yield 'hello';
            },
        );

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'hello';
        })();

        $response = Response::stream($generator);

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamWithResource(): void
    {
        $resource = \fopen('php://memory', 'r+b');

        self::assertIsResource($resource);

        $response = Response::stream($resource);

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamWithStreamInterface(): void
    {
        $stream = Stream::fromGenerator(
            static function (): \Generator {
                yield 'hello';
            },
        );

        $response = Response::stream($stream);

        self::assertSame($stream, $response->body);
    }

    public function testWithHeaderAppendsHeader(): void
    {
        $response = new Response();
        $updated = $response->withHeader(new Header('X-Foo', 'bar'));

        self::assertNotSame($response, $updated);
        self::assertCount(0, $response->headers);
        self::assertCount(1, $updated->headers);
    }

    public function testWithHeaderReplacesExistingHeader(): void
    {
        $response = new Response(
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );

        $updated = $response->withHeader(
            header: new Header('Content-Type', 'application/json'),
            replace: true,
        );

        self::assertCount(1, $updated->headers);
        self::assertSame('application/json', $updated->headers[0]->value);
    }

    public function testWithHeaderReplaceCaseInsensitive(): void
    {
        $response = new Response(
            headers: [
                new Header('content-type', 'text/plain'),
            ],
        );

        $updated = $response->withHeader(
            header: new Header('Content-Type', 'application/json'),
            replace: true,
        );

        self::assertCount(1, $updated->headers);
        self::assertSame('application/json', $updated->headers[0]->value);
    }

    public function testWithHeadersAppendsMultiple(): void
    {
        $response = new Response();
        $updated = $response->withHeaders(
            headers: [
                new Header('X-Foo', 'foo'),
                new Header('X-Bar', 'bar'),
            ],
        );

        self::assertCount(2, $updated->headers);
    }

    public function testWithHeadersReplacesExisting(): void
    {
        $response = new Response(
            headers: [
                new Header('X-Foo', 'old'),
            ],
        );

        $updated = $response->withHeaders(
            headers: [
                new Header('X-Foo', 'new'),
            ],
            replace: true,
        );

        self::assertCount(1, $updated->headers);
        self::assertSame('new', $updated->headers[0]->value);
    }

    public function testWithoutHeaderRemovesHeader(): void
    {
        $response = new Response(
            headers: [
                new Header('X-Foo', 'bar'),
                new Header('X-Baz', 'qux'),
            ],
        );

        $updated = $response->withoutHeader('X-Foo');

        $key = \array_key_first($updated->headers);

        self::assertNotSame($response, $updated);
        self::assertCount(1, $updated->headers);
        self::assertNotNull($key);
        self::assertSame('X-Baz', $updated->headers[$key]->name);
    }

    public function testWithoutHeaderNoOpWhenMissing(): void
    {
        $response = new Response(
            headers: [
                new Header('X-Foo', 'bar'),
            ],
        );

        $updated = $response->withoutHeader('X-Missing');

        self::assertCount(1, $updated->headers);
    }

    public function testWithCookieAppendsCookie(): void
    {
        $response = new Response();
        $updated = $response->withCookie(new Cookie('session', 'abc', 0));

        self::assertNotSame($response, $updated);
        self::assertCount(0, $response->headers);
        self::assertCount(1, $updated->headers);
    }

    public function testWithCookieReplacesCookie(): void
    {
        $response = new Response(
            headers: [
                new Cookie('session', 'old', 0),
            ],
        );

        $updated = $response->withCookie(
            cookie: new Cookie('session', 'new', 0),
            replace: true,
        );

        self::assertCount(1, $updated->headers);
        self::assertSame('new', $updated->headers[0]->value);
    }

    public function testWithCookieReplaceDoesNotReplaceRegularHeader(): void
    {
        $response = new Response(
            headers: [
                new Header('session', 'old'),
            ],
        );

        $updated = $response->withCookie(
            cookie: new Cookie('session', 'new', 0),
            replace: true,
        );

        self::assertCount(2, $updated->headers);
    }

    public function testWithCookiesAppendsMultiple(): void
    {
        $response = new Response();
        $updated = $response->withCookies(
            cookies: [
                new Cookie('foo', 'a', 0),
                new Cookie('bar', 'b', 0),
            ],
        );

        self::assertCount(2, $updated->headers);
    }

    public function testWithCookiesReplacesExisting(): void
    {
        $response = new Response(
            headers: [
                new Cookie('foo', 'old', 0),
            ],
        );
        $updated = $response->withCookies(
            cookies: [
                new Cookie('foo', 'new', 0),
            ],
            replace: true,
        );

        self::assertCount(1, $updated->headers);
        self::assertSame('new', $updated->headers[0]->value);
    }

    public function testWithoutCookieRemovesCookie(): void
    {
        $response = new Response(
            headers: [
                new Cookie('session', 'abc', 0),
                new Header('X-Foo', 'bar'),
            ],
        );

        $updated = $response->withoutCookie('session');

        $key = \array_key_first($updated->headers);

        self::assertCount(1, $updated->headers);
        self::assertNotNull($key);
        self::assertInstanceOf(Header::class, $updated->headers[$key]);
    }

    public function testWithoutCookieNoOpWhenMissing(): void
    {
        $response = new Response(
            headers: [
                new Cookie('session', 'abc', 0),
            ],
        );

        $updated = $response->withoutCookie('other');

        self::assertCount(1, $updated->headers);
    }

    public function testWithResponseCodeWithEnum(): void
    {
        $response = new Response();
        $updated = $response->withResponseCode(ResponseCode::NOT_FOUND);

        self::assertNotSame($response, $updated);
        self::assertSame(ResponseCode::NOT_FOUND, $updated->responseCode);
        self::assertSame(ResponseCode::OK, $response->responseCode);
    }

    public function testWithResponseCodeWithInt(): void
    {
        $response = new Response();
        $updated = $response->withResponseCode(404);

        self::assertSame(ResponseCode::NOT_FOUND, $updated->responseCode);
    }

    public function testWithBodyString(): void
    {
        $response = new Response();
        $updated = $response->withBody('new body');

        self::assertNotSame($response, $updated);
        self::assertSame('new body', $updated->body);
        self::assertSame('', $response->body);
    }

    public function testWithBodyStream(): void
    {
        $stream = Stream::fromGenerator(
            static function (): \Generator {
                yield 'hello';
            },
        );

        $response = new Response();
        $updated = $response->withBody($stream);

        self::assertSame($stream, $updated->body);
    }

    private function makeContainerWithRoute(
        string $name,
        string $uri = '/home',
    ): Container {
        return (new Container())->persistent(
            class: new StaticRouter(
                routes: [
                    new Route(
                        method: null,
                        uri: $uri,
                        controller: self::class,
                        action: 'index',
                        name: $name,
                    ),
                ],
            ),
        );
    }

    public function testRedirectRouteSetsLocationHeader(): void
    {
        $response = Response::redirectRoute('home')->toResponse(
            $this->makeContainerWithRoute('home'),
        );

        self::assertSame('Location', $response->headers[0]->name);
        self::assertSame('/home', $response->headers[0]->value);
    }

    public function testRedirectRouteDefaultResponseCode(): void
    {
        $response = Response::redirectRoute('home')->toResponse(
            $this->makeContainerWithRoute('home'),
        );

        self::assertSame(ResponseCode::FOUND, $response->responseCode);
    }

    public function testRedirectRouteWithCustomResponseCode(): void
    {
        $response = Response::redirectRoute(
            name: 'home',
            responseCode: ResponseCode::MOVED_PERMANENTLY,
        )->toResponse(
            $this->makeContainerWithRoute('home'),
        );

        self::assertSame(ResponseCode::MOVED_PERMANENTLY, $response->responseCode);
    }

    public function testRedirectRouteThrowsWhenRouteNotFound(): void
    {
        $this->expectException(HttpException::class);

        Response::redirectRoute('missing')->toResponse(
            $this->makeContainerWithRoute('home'),
        );
    }

    public function testStreamCsvWithClosureReturnsStreamBody(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                ];
            },
        );

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamCsvWithGeneratorReturnsStreamBody(): void
    {
        $generator = (static function (): \Generator {
            yield [
                'a',
                'b',
            ];
        })();

        $response = Response::streamCsv(
            generator: $generator,
        );

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamCsvDefaultResponseCodeIsOk(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertSame(ResponseCode::OK, $response->responseCode);
    }

    public function testStreamCsvPropagatesCustomResponseCode(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield from [];
            },
            responseCode: ResponseCode::ACCEPTED,
        );

        self::assertSame(ResponseCode::ACCEPTED, $response->responseCode);
    }

    public function testStreamCsvIncludesCsvContentTypeHeaderFromProxy(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        $contentType = null;

        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'Content-Type') === 0) {
                $contentType = $header->value;
            }
        }

        self::assertSame('text/csv; charset=utf-8', $contentType);
    }

    public function testStreamCsvMergesCustomHeaders(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield from [];
            },
            headers: [
                new Header('X-Custom', 'value'),
            ],
        );

        $custom = null;

        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'X-Custom') === 0) {
                $custom = $header->value;
            }
        }

        self::assertSame('value', $custom);
    }

    public function testStreamCsvBodyContainsColumnsAndRows(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield [
                    'value-a',
                    'value-b',
                ];
            },
            columns: [
                'col-a',
                'col-b',
            ],
        );

        /** @var StreamInterface $body */
        $body = $response->body;

        self::assertSame("col-a,col-b\nvalue-a,value-b\n", $body->getContents());
    }

    public function testStreamCsvBodyHonorsSeparatorOption(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                ];
            },
            separator: ';',
        );

        /** @var StreamInterface $body */
        $body = $response->body;

        self::assertSame("a;b\n", $body->getContents());
    }

    public function testStreamCsvBodyHonorsEnclosureOption(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield [
                    'a,b',
                ];
            },
            enclosure: '\'',
        );

        /** @var StreamInterface $body */
        $body = $response->body;

        self::assertSame("'a,b'\n", $body->getContents());
    }

    public function testStreamCsvBodyHonorsEolOption(): void
    {
        $response = Response::streamCsv(
            generator: static function (): \Generator {
                yield [
                    'a',
                ];
            },
            eol: "\r\n",
        );

        /** @var StreamInterface $body */
        $body = $response->body;

        self::assertSame("a\r\n", $body->getContents());
    }

    public function testStreamSseWithClosureReturnsStreamBody(): void
    {
        $response = Response::streamSse(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'hello',
                );
            },
        );

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamSseWithGeneratorReturnsStreamBody(): void
    {
        $generator = (static function (): \Generator {
            yield SseEvent::create(
                data: 'hello',
            );
        })();

        $response = Response::streamSse(
            generator: $generator,
        );

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamSseDefaultResponseCodeIsOk(): void
    {
        $response = Response::streamSse(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertSame(ResponseCode::OK, $response->responseCode);
    }

    public function testStreamSsePropagatesCustomResponseCode(): void
    {
        $response = Response::streamSse(
            generator: static function (): \Generator {
                yield from [];
            },
            responseCode: ResponseCode::ACCEPTED,
        );

        self::assertSame(ResponseCode::ACCEPTED, $response->responseCode);
    }

    public function testStreamSseIncludesEventStreamHeadersFromProxy(): void
    {
        $response = Response::streamSse(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        $contentType = null;
        $cacheControl = null;

        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'Content-Type') === 0) {
                $contentType = $header->value;
            }

            if (\strcasecmp($header->name, 'Cache-Control') === 0) {
                $cacheControl = $header->value;
            }
        }

        self::assertSame('text/event-stream', $contentType);
        self::assertSame('no-cache', $cacheControl);
    }

    public function testStreamSseMergesCustomHeaders(): void
    {
        $response = Response::streamSse(
            generator: static function (): \Generator {
                yield from [];
            },
            headers: [
                new Header('X-Custom', 'value'),
            ],
        );

        $custom = null;

        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'X-Custom') === 0) {
                $custom = $header->value;
            }
        }

        self::assertSame('value', $custom);
    }

    public function testStreamSseBodyFormatsEventsInOrder(): void
    {
        $response = Response::streamSse(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'first',
                    id: 'evt-1',
                );

                yield SseEvent::create(
                    data: 'second',
                );
            },
        );

        /** @var StreamInterface $body */
        $body = $response->body;

        self::assertSame(
            "id: evt-1\ndata: first\n\ndata: second\n\n",
            $body->getContents(),
        );
    }

    public function testStreamJsonWithClosureReturnsStreamBody(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield [
                    'a' => 1,
                ];
            },
        );

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamJsonWithGeneratorReturnsStreamBody(): void
    {
        $generator = (static function (): \Generator {
            yield [
                'a' => 1,
            ];
        })();

        $response = Response::streamJson(
            stream: $generator,
        );

        self::assertInstanceOf(StreamInterface::class, $response->body);
    }

    public function testStreamJsonDefaultResponseCodeIsOk(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield from [];
            },
        );

        self::assertSame(ResponseCode::OK, $response->responseCode);
    }

    public function testStreamJsonPropagatesCustomResponseCode(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield from [];
            },
            responseCode: ResponseCode::ACCEPTED,
        );

        self::assertSame(ResponseCode::ACCEPTED, $response->responseCode);
    }

    public function testStreamJsonIncludesJsonlContentTypeByDefault(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield from [];
            },
        );

        $contentType = null;

        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'Content-Type') === 0) {
                $contentType = $header->value;
            }
        }

        self::assertSame('application/x-ndjson', $contentType);
    }

    public function testStreamJsonIncludesRfc7464ContentTypeWhenFormatSet(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield from [];
            },
            format: JsonStreamFormat::RFC7464,
        );

        $contentType = null;

        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'Content-Type') === 0) {
                $contentType = $header->value;
            }
        }

        self::assertSame('application/json-seq', $contentType);
    }

    public function testStreamJsonMergesCustomHeaders(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield from [];
            },
            headers: [
                new Header('X-Custom', 'value'),
            ],
        );

        $custom = null;

        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'X-Custom') === 0) {
                $custom = $header->value;
            }
        }

        self::assertSame('value', $custom);
    }

    public function testStreamJsonBodyEncodesItemsAsJsonl(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield [
                    'a' => 1,
                ];

                yield [
                    'b' => 2,
                ];
            },
        );

        /** @var StreamInterface $body */
        $body = $response->body;

        self::assertSame("{\"a\":1}\n{\"b\":2}\n", $body->getContents());
    }

    public function testStreamJsonBodyEncodesItemsAsRfc7464(): void
    {
        $response = Response::streamJson(
            stream: static function (): \Generator {
                yield [
                    'a' => 1,
                ];

                yield [
                    'b' => 2,
                ];
            },
            format: JsonStreamFormat::RFC7464,
        );

        /** @var StreamInterface $body */
        $body = $response->body;

        self::assertSame("\x1e{\"a\":1}\n\x1e{\"b\":2}\n", $body->getContents());
    }
}
