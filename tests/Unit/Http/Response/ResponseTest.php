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
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\HttpVersion;
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

    public function testWithHttpVersion(): void
    {
        $response = new Response(
            httpVersion: HttpVersion::V2_0,
        );

        self::assertSame(HttpVersion::V2_0, $response->httpVersion);

        $response = $response->withHttpVersion(HttpVersion::V3_0);

        self::assertSame(HttpVersion::V3_0, $response->httpVersion);
    }

    public function testHasHeaderReturnsTrueWhenHeaderPresent(): void
    {
        $response = (new Response())->withHeader(
            new Header('Content-Type', 'text/plain'),
        );

        self::assertTrue($response->hasHeader('Content-Type'));
    }

    public function testHasHeaderReturnsFalseWhenHeaderAbsent(): void
    {
        $response = new Response();

        self::assertFalse($response->hasHeader('Content-Type'));
    }

    public function testHasHeaderIsCaseInsensitive(): void
    {
        $response = (new Response())->withHeader(
            new Header('Content-Type', 'text/plain'),
        );

        self::assertTrue($response->hasHeader('content-type'));
    }

    public function testHasHeaderMatchesCookieByName(): void
    {
        $response = (new Response())->withCookie(
            new Cookie('session', 'abc', 0),
        );

        self::assertTrue($response->hasHeader('session'));
    }

    public function testHasHeaderSkipsNonMatchingHeaders(): void
    {
        $response = (new Response())->withHeader(
            new Header('X-Custom', 'value'),
        );

        self::assertFalse($response->hasHeader('Content-Type'));
    }

    public function testHasCookieReturnsTrueWhenCookiePresent(): void
    {
        $response = (new Response())->withCookie(
            new Cookie('session', 'abc', 0),
        );

        self::assertTrue($response->hasCookie('session'));
    }

    public function testHasCookieReturnsFalseWhenCookieAbsent(): void
    {
        $response = new Response();

        self::assertFalse($response->hasCookie('session'));
    }

    public function testHasCookieIsCaseInsensitive(): void
    {
        $response = (new Response())->withCookie(
            new Cookie('SESSION', 'abc', 0),
        );

        self::assertTrue($response->hasCookie('session'));
    }

    public function testHasCookieIgnoresHeadersWithSameName(): void
    {
        $response = (new Response())->withHeader(
            new Header('session', 'abc'),
        );

        self::assertFalse($response->hasCookie('session'));
    }

    public function testHasCookieSkipsNonMatchingCookies(): void
    {
        $response = (new Response())->withCookie(
            new Cookie('other', 'value', 0),
        );

        self::assertFalse($response->hasCookie('session'));
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
        string $path = '/home',
    ): Container {
        return (new Container())->singleton(
            class: new StaticRouter(
                routes: [
                    new Route(
                        method: null,
                        path: $path,
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

    private function findHeader(
        Response $response,
        string $name,
    ): ?HeaderInterface {
        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, $name) === 0) {
                return $header;
            }
        }

        return null;
    }

    public function testDownloadWithStringBody(): void
    {
        $response = Response::download(
            body: 'binary-content',
            filename: 'file.bin',
        );

        self::assertSame('binary-content', $response->body);
    }

    public function testDownloadWithStreamBody(): void
    {
        $stream = Stream::fromGenerator(
            static function (): \Generator {
                yield 'chunk';
            },
        );

        $response = Response::download(
            body: $stream,
            filename: 'file.bin',
        );

        self::assertSame($stream, $response->body);
    }

    public function testDownloadSetsDefaultContentTypeToOctetStream(): void
    {
        $response = Response::download(
            body: 'data',
            filename: 'file.bin',
        );

        $contentType = $this->findHeader($response, 'Content-Type');

        self::assertNotNull($contentType);
        self::assertSame('application/octet-stream', $contentType->value);
    }

    public function testDownloadAcceptsCustomContentType(): void
    {
        $response = Response::download(
            body: 'data',
            filename: 'report.pdf',
            contentType: 'application/pdf',
        );

        $contentType = $this->findHeader($response, 'Content-Type');

        self::assertNotNull($contentType);
        self::assertSame('application/pdf', $contentType->value);
    }

    public function testDownloadReplacesContentTypeFromStreamBody(): void
    {
        $stream = Stream::fromJson(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];
            },
        );

        $response = Response::download(
            body: $stream,
            filename: 'file.bin',
        );

        $contentTypes = \array_values(
            \array_filter(
                $response->headers,
                static fn (HeaderInterface $header): bool => \strcasecmp($header->name, 'Content-Type') === 0,
            ),
        );

        self::assertCount(1, $contentTypes);
        self::assertSame('application/octet-stream', $contentTypes[0]->value);
    }

    public function testDownloadMergesAdditionalHeaders(): void
    {
        $response = Response::download(
            body: 'data',
            filename: 'file.bin',
            headers: [
                new Header('X-Custom', 'value'),
            ],
        );

        $custom = $this->findHeader($response, 'X-Custom');

        self::assertNotNull($custom);
        self::assertSame('value', $custom->value);
    }

    public function testDownloadSetsContentDispositionHeader(): void
    {
        $response = Response::download(
            body: 'data',
            filename: 'report.csv',
        );

        $disposition = $this->findHeader($response, 'Content-Disposition');

        self::assertNotNull($disposition);
        self::assertSame('attachment; filename="report.csv"', $disposition->value);
    }

    public function testDownloadDefaultResponseCodeIsOk(): void
    {
        $response = Response::download(
            body: 'data',
            filename: 'file.bin',
        );

        self::assertSame(ResponseCode::OK, $response->responseCode);
    }

    public function testDownloadAcceptsCustomResponseCodeEnum(): void
    {
        $response = Response::download(
            body: 'data',
            filename: 'file.bin',
            responseCode: ResponseCode::CREATED,
        );

        self::assertSame(ResponseCode::CREATED, $response->responseCode);
    }

    public function testDownloadAcceptsIntResponseCode(): void
    {
        $response = Response::download(
            body: 'data',
            filename: 'file.bin',
            responseCode: 201,
        );

        self::assertSame(ResponseCode::CREATED, $response->responseCode);
    }

    public function testWithDownloadSetsContentDispositionHeader(): void
    {
        $response = new Response();
        $updated = $response->withDownload(
            filename: 'report.csv',
        );

        $disposition = $this->findHeader($updated, 'Content-Disposition');

        self::assertNotNull($disposition);
        self::assertSame('attachment; filename="report.csv"', $disposition->value);
    }

    public function testWithDownloadReturnsNewInstanceWithoutMutatingOriginal(): void
    {
        $response = new Response();
        $updated = $response->withDownload(
            filename: 'report.csv',
        );

        self::assertNotSame($response, $updated);
        self::assertCount(0, $response->headers);
    }

    public function testWithDownloadEscapesDoubleQuotesInAsciiFilename(): void
    {
        $response = (new Response())->withDownload(
            filename: 'say "hi".txt',
        );

        $disposition = $this->findHeader($response, 'Content-Disposition');

        self::assertNotNull($disposition);
        self::assertSame('attachment; filename="say \\"hi\\".txt"', $disposition->value);
    }

    public function testWithDownloadStripsForwardSlashes(): void
    {
        $response = (new Response())->withDownload(
            filename: '../etc/passwd',
        );

        $disposition = $this->findHeader($response, 'Content-Disposition');

        self::assertNotNull($disposition);
        self::assertSame('attachment; filename="..etcpasswd"', $disposition->value);
    }

    public function testWithDownloadStripsBackslashes(): void
    {
        $response = (new Response())->withDownload(
            filename: 'path\\to\\file.txt',
        );

        $disposition = $this->findHeader($response, 'Content-Disposition');

        self::assertNotNull($disposition);
        self::assertSame('attachment; filename="pathtofile.txt"', $disposition->value);
    }

    public function testWithDownloadStripsNullByte(): void
    {
        $response = (new Response())->withDownload(
            filename: "evil\0.txt",
        );

        $disposition = $this->findHeader($response, 'Content-Disposition');

        self::assertNotNull($disposition);
        self::assertSame('attachment; filename="evil.txt"', $disposition->value);
    }

    public function testWithDownloadEncodesNonAsciiFilenameWithRfc5987(): void
    {
        $response = (new Response())->withDownload(
            filename: 'résumé.pdf',
        );

        $disposition = $this->findHeader($response, 'Content-Disposition');

        self::assertNotNull($disposition);
        self::assertSame(
            'attachment; filename="r__sum__.pdf"; filename*=UTF-8\'\'r%C3%A9sum%C3%A9.pdf',
            $disposition->value,
        );
    }

    public function testWithDownloadReplacesExistingContentDispositionHeader(): void
    {
        $response = (new Response())->withHeader(
            new Header('Content-Disposition', 'inline'),
        );

        $updated = $response->withDownload(
            filename: 'file.bin',
        );

        $dispositions = \array_values(
            \array_filter(
                $updated->headers,
                static fn (HeaderInterface $header): bool => \strcasecmp($header->name, 'Content-Disposition') === 0,
            ),
        );

        self::assertCount(1, $dispositions);
        self::assertSame('attachment; filename="file.bin"', $dispositions[0]->value);
    }

    public function testWithoutDownloadRemovesContentDispositionHeader(): void
    {
        $response = (new Response())->withDownload(
            filename: 'file.bin',
        );

        $updated = $response->withoutDownload();

        self::assertNull($this->findHeader($updated, 'Content-Disposition'));
    }

    public function testWithoutDownloadOnResponseWithoutDispositionIsNoOp(): void
    {
        $response = new Response();
        $updated = $response->withoutDownload();

        self::assertNull($this->findHeader($updated, 'Content-Disposition'));
        self::assertCount(0, $updated->headers);
    }

    public function testWithoutDownloadReturnsNewInstance(): void
    {
        $response = (new Response())->withDownload(
            filename: 'file.bin',
        );

        $updated = $response->withoutDownload();

        self::assertNotSame($response, $updated);
        self::assertCount(1, $response->headers);
    }

    public function testWithVaryAddsHeaderWhenNotPresent(): void
    {
        $response = (new Response())->withVary('Accept');

        $vary = $this->findHeader($response, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept', $vary->value);
    }

    public function testWithVaryWithMultipleHeaders(): void
    {
        $response = (new Response())->withVary('Accept', 'Accept-Encoding', 'Accept-Language');

        $vary = $this->findHeader($response, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept, Accept-Encoding, Accept-Language', $vary->value);
    }

    public function testWithVaryMergesWithExistingHeader(): void
    {
        $response = (new Response())
            ->withVary('Accept')
            ->withVary('Accept-Encoding');

        $vary = $this->findHeader($response, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept, Accept-Encoding', $vary->value);
    }

    public function testWithVaryDeduplicatesCaseInsensitively(): void
    {
        $response = (new Response())
            ->withVary('Accept')
            ->withVary('accept');

        $vary = $this->findHeader($response, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept', $vary->value);
    }

    public function testWithVaryPreservesExistingOrderAndAppendsNew(): void
    {
        $response = (new Response())
            ->withVary('Accept-Encoding')
            ->withVary('Accept');

        $vary = $this->findHeader($response, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept-Encoding, Accept', $vary->value);
    }

    public function testWithVaryWithZeroArgsIsNoOpAndPreservesExisting(): void
    {
        $original = (new Response())->withVary('Accept');
        $updated = $original->withVary();

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept', $vary->value);
    }

    public function testWithVaryWithZeroArgsOnResponseWithoutVaryAddsNothing(): void
    {
        $response = (new Response())->withVary();

        self::assertNull($this->findHeader($response, 'Vary'));
    }

    public function testWithVaryReturnsNewInstance(): void
    {
        $response = new Response();
        $updated = $response->withVary('Accept');

        self::assertNotSame($response, $updated);
        self::assertCount(0, $response->headers);
    }

    public function testWithVaryTrimsWhitespaceFromExistingEntries(): void
    {
        $response = (new Response())->withHeader(
            new Header('Vary', 'Accept,  Accept-Encoding '),
        );

        $updated = $response->withVary('Accept-Language');

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept, Accept-Encoding, Accept-Language', $vary->value);
    }

    public function testWithVarySkipsEmptyEntriesInExistingHeader(): void
    {
        $response = (new Response())->withHeader(
            new Header('Vary', 'Accept,,Accept-Encoding'),
        );

        $updated = $response->withVary('Accept-Language');

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept, Accept-Encoding, Accept-Language', $vary->value);
    }

    public function testWithVarySkipsNonVaryHeadersDuringParse(): void
    {
        $response = (new Response())
            ->withHeader(new Header('Content-Type', 'text/plain'))
            ->withHeader(new Header('Vary', 'Accept'));

        $updated = $response->withVary('Accept-Encoding');

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept, Accept-Encoding', $vary->value);
    }

    public function testWithoutVaryWithNoArgsRemovesEntireHeader(): void
    {
        $response = (new Response())->withVary('Accept', 'Accept-Encoding');

        $updated = $response->withoutVary();

        self::assertNull($this->findHeader($updated, 'Vary'));
    }

    public function testWithoutVaryWithSpecificEntryRemovesOnlyThatEntry(): void
    {
        $response = (new Response())->withVary('Accept', 'Accept-Encoding');

        $updated = $response->withoutVary('Accept');

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept-Encoding', $vary->value);
    }

    public function testWithoutVaryIsCaseInsensitive(): void
    {
        $response = (new Response())->withVary('Accept', 'Accept-Encoding');

        $updated = $response->withoutVary('accept');

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept-Encoding', $vary->value);
    }

    public function testWithoutVaryWithMultipleArgs(): void
    {
        $response = (new Response())->withVary('Accept', 'Accept-Encoding', 'Accept-Language');

        $updated = $response->withoutVary('Accept', 'Accept-Language');

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept-Encoding', $vary->value);
    }

    public function testWithoutVaryRemovingAllEntriesRemovesHeader(): void
    {
        $response = (new Response())->withVary('Accept');

        $updated = $response->withoutVary('Accept');

        self::assertNull($this->findHeader($updated, 'Vary'));
    }

    public function testWithoutVaryOnResponseWithoutVaryIsNoOp(): void
    {
        $response = new Response();
        $updated = $response->withoutVary();

        self::assertNull($this->findHeader($updated, 'Vary'));
        self::assertCount(0, $updated->headers);
    }

    public function testWithoutVaryWithSpecificArgOnResponseWithoutVaryIsNoOp(): void
    {
        $response = new Response();
        $updated = $response->withoutVary('Accept');

        self::assertNull($this->findHeader($updated, 'Vary'));
        self::assertCount(0, $updated->headers);
    }

    public function testWithoutVaryWithEntryNotInListIsNoOp(): void
    {
        $response = (new Response())->withVary('Accept');

        $updated = $response->withoutVary('Accept-Language');

        $vary = $this->findHeader($updated, 'Vary');

        self::assertNotNull($vary);
        self::assertSame('Accept', $vary->value);
    }

    public function testWithoutVaryReturnsNewInstance(): void
    {
        $response = (new Response())->withVary('Accept');
        $updated = $response->withoutVary();

        self::assertNotSame($response, $updated);

        $vary = $this->findHeader($response, 'Vary');

        self::assertNotNull($vary);
    }

    public function testWithEtagSetsStrongEtagHeaderByDefault(): void
    {
        $response = (new Response())->withEtag('abc123');

        $etag = $this->findHeader($response, 'ETag');

        self::assertNotNull($etag);
        self::assertSame('"abc123"', $etag->value);
    }

    public function testWithEtagSetsWeakEtagWhenWeakIsTrue(): void
    {
        $response = (new Response())->withEtag(
            etag: 'abc123',
            weak: true,
        );

        $etag = $this->findHeader($response, 'ETag');

        self::assertNotNull($etag);
        self::assertSame('W/"abc123"', $etag->value);
    }

    public function testWithEtagReplacesExistingEtagHeader(): void
    {
        $response = (new Response())
            ->withEtag('first')
            ->withEtag('second');

        $etags = \array_values(
            \array_filter(
                $response->headers,
                static fn (HeaderInterface $header): bool => \strcasecmp($header->name, 'ETag') === 0,
            ),
        );

        self::assertCount(1, $etags);
        self::assertSame('"second"', $etags[0]->value);
    }

    public function testWithEtagReturnsNewInstance(): void
    {
        $response = new Response();
        $updated = $response->withEtag('abc123');

        self::assertNotSame($response, $updated);
        self::assertCount(0, $response->headers);
    }

    public function testWithLastModifiedFormatsAsRfc7231(): void
    {
        $when = new \DateTimeImmutable('2026-01-15 10:30:00', new \DateTimeZone('UTC'));

        $response = (new Response())->withLastModified($when);

        $lastModified = $this->findHeader($response, 'Last-Modified');

        self::assertNotNull($lastModified);
        self::assertSame('Thu, 15 Jan 2026 10:30:00 GMT', $lastModified->value);
    }

    public function testWithLastModifiedConvertsToUtc(): void
    {
        $when = new \DateTimeImmutable('2026-01-15 12:30:00', new \DateTimeZone('America/New_York'));

        $response = (new Response())->withLastModified($when);

        $lastModified = $this->findHeader($response, 'Last-Modified');

        self::assertNotNull($lastModified);
        self::assertSame('Thu, 15 Jan 2026 17:30:00 GMT', $lastModified->value);
    }

    public function testWithLastModifiedAcceptsMutableDateTime(): void
    {
        $when = new \DateTime('2026-01-15 10:30:00', new \DateTimeZone('UTC'));

        $response = (new Response())->withLastModified($when);
        $when->setTimezone(new \DateTimeZone('America/New_York'));

        $lastModified = $this->findHeader($response, 'Last-Modified');

        self::assertNotNull($lastModified);
        self::assertSame('Thu, 15 Jan 2026 10:30:00 GMT', $lastModified->value);
    }

    public function testWithLastModifiedReplacesExistingHeader(): void
    {
        $response = (new Response())
            ->withLastModified(new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC')))
            ->withLastModified(new \DateTimeImmutable('2026-02-01 00:00:00', new \DateTimeZone('UTC')));

        $entries = \array_values(
            \array_filter(
                $response->headers,
                static fn (HeaderInterface $header): bool => \strcasecmp($header->name, 'Last-Modified') === 0,
            ),
        );

        self::assertCount(1, $entries);
        self::assertSame('Sun, 01 Feb 2026 00:00:00 GMT', $entries[0]->value);
    }

    public function testWithLastModifiedReturnsNewInstance(): void
    {
        $response = new Response();
        $updated = $response->withLastModified(new \DateTimeImmutable('2026-01-15', new \DateTimeZone('UTC')));

        self::assertNotSame($response, $updated);
        self::assertCount(0, $response->headers);
    }

    public function testWithCacheControlMaxAge(): void
    {
        $response = (new Response())->withCacheControl(
            maxAge: 3600,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('max-age=3600', $cc->value);
    }

    public function testWithCacheControlSMaxAge(): void
    {
        $response = (new Response())->withCacheControl(
            sMaxAge: 7200,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('s-maxage=7200', $cc->value);
    }

    public function testWithCacheControlPublic(): void
    {
        $response = (new Response())->withCacheControl(
            public: true,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('public', $cc->value);
    }

    public function testWithCacheControlPrivate(): void
    {
        $response = (new Response())->withCacheControl(
            private: true,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('private', $cc->value);
    }

    public function testWithCacheControlNoCache(): void
    {
        $response = (new Response())->withCacheControl(
            noCache: true,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('no-cache', $cc->value);
    }

    public function testWithCacheControlNoStore(): void
    {
        $response = (new Response())->withCacheControl(
            noStore: true,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('no-store', $cc->value);
    }

    public function testWithCacheControlMustRevalidate(): void
    {
        $response = (new Response())->withCacheControl(
            mustRevalidate: true,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('must-revalidate', $cc->value);
    }

    public function testWithCacheControlProxyRevalidate(): void
    {
        $response = (new Response())->withCacheControl(
            proxyRevalidate: true,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('proxy-revalidate', $cc->value);
    }

    public function testWithCacheControlImmutable(): void
    {
        $response = (new Response())->withCacheControl(
            immutable: true,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('immutable', $cc->value);
    }

    public function testWithCacheControlStaleWhileRevalidate(): void
    {
        $response = (new Response())->withCacheControl(
            staleWhileRevalidate: 60,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('stale-while-revalidate=60', $cc->value);
    }

    public function testWithCacheControlStaleIfError(): void
    {
        $response = (new Response())->withCacheControl(
            staleIfError: 120,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('stale-if-error=120', $cc->value);
    }

    public function testWithCacheControlCombinesMultipleDirectivesInOrder(): void
    {
        $response = (new Response())->withCacheControl(
            maxAge: 3600,
            sMaxAge: 7200,
            public: true,
            mustRevalidate: true,
            staleWhileRevalidate: 60,
        );

        $cc = $this->findHeader($response, 'Cache-Control');

        self::assertNotNull($cc);
        self::assertSame('public, must-revalidate, max-age=3600, s-maxage=7200, stale-while-revalidate=60', $cc->value);
    }

    public function testWithCacheControlWithNoDirectivesRemovesHeader(): void
    {
        $response = (new Response())
            ->withCacheControl(maxAge: 3600)
            ->withCacheControl();

        self::assertNull($this->findHeader($response, 'Cache-Control'));
    }

    public function testWithCacheControlThrowsWhenPublicAndPrivateBothTrue(): void
    {
        self::expectException(HttpException::class);

        (new Response())->withCacheControl(
            public: true,
            private: true,
        );
    }

    public function testWithCacheControlReplacesExistingHeader(): void
    {
        $response = (new Response())
            ->withCacheControl(maxAge: 3600)
            ->withCacheControl(noStore: true);

        $entries = \array_values(
            \array_filter(
                $response->headers,
                static fn (HeaderInterface $header): bool => \strcasecmp($header->name, 'Cache-Control') === 0,
            ),
        );

        self::assertCount(1, $entries);
        self::assertSame('no-store', $entries[0]->value);
    }

    public function testWithCacheControlReturnsNewInstance(): void
    {
        $response = new Response();
        $updated = $response->withCacheControl(maxAge: 3600);

        self::assertNotSame($response, $updated);
        self::assertCount(0, $response->headers);
    }
}
