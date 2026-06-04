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

namespace Unit\Http\Request;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Router\StubDispatchableRoute;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Router\DispatchableRouteInterface;

class RequestTest extends TestCase
{
    private function makeRequest(
        ?DispatchableRouteInterface $route = null,
        ?HeaderContextInterface $headers = null,
        ?InputContextInterface $cookies = null,
        ?InputContextInterface $get = null,
        ?InputContextInterface $post = null,
        ?UploadedFilesContextInterface $files = null,
        ?BodyContextInterface $body = null,
    ): Request {
        return new Request(
            route: $route,
            headers: $headers ?? new StubHeaderContext(),
            cookies: $cookies ?? new StubInputContext(),
            get: $get ?? new StubInputContext(),
            post: $post ?? new StubInputContext(),
            files: $files ?? new StubUploadedFilesContext(),
            body: $body ?? new StubBodyContext(),
        );
    }

    public function testRouteIsUninitializedByDefault(): void
    {
        $request = $this->makeRequest();

        self::assertFalse(isset($request->route));
    }

    public function testRouteIsSetWhenPassedToConstructor(): void
    {
        $route = new StubDispatchableRoute();
        $request = $this->makeRequest(
            route: $route,
        );

        self::assertSame($route, $request->route);
    }

    public function testWithRouteReturnsNewInstance(): void
    {
        $request = $this->makeRequest();

        self::assertNotSame(
            $request,
            $request->withRoute(new StubDispatchableRoute()),
        );
    }

    public function testWithRouteSetsRouteOnNewInstance(): void
    {
        $route = new StubDispatchableRoute();
        $new = $this->makeRequest()->withRoute($route);

        self::assertSame($route, $new->route);
    }

    public function testWithRouteDoesNotModifyOriginal(): void
    {
        $request = $this->makeRequest();

        (void) $request->withRoute(new StubDispatchableRoute());

        self::assertFalse(isset($request->route));
    }

    public function testWithRoutePreservesContexts(): void
    {
        $headers = new StubHeaderContext();
        $cookies = new StubInputContext();
        $get = new StubInputContext();
        $post = new StubInputContext();
        $files = new StubUploadedFilesContext();
        $body = new StubBodyContext();

        $new = $this->makeRequest(
            headers: $headers,
            cookies: $cookies,
            get: $get,
            post: $post,
            files: $files,
            body: $body,
        )->withRoute(
            route: new StubDispatchableRoute(),
        );

        self::assertSame($headers, $new->headers);
        self::assertSame($cookies, $new->cookies);
        self::assertSame($get, $new->get);
        self::assertSame($post, $new->post);
        self::assertSame($files, $new->files);
        self::assertSame($body, $new->body);
    }

    public function testInputWithGetContextReturnsGetContext(): void
    {
        $get = new StubInputContext();
        $request = $this->makeRequest(
            get: $get,
        );

        self::assertSame($get, $request->input(InputContext::GET));
    }

    public function testInputWithPostContextReturnsPostContext(): void
    {
        $post = new StubInputContext();
        $request = $this->makeRequest(
            post: $post,
        );

        self::assertSame($post, $request->input(InputContext::POST));
    }

    public function testInputWithCookieContextReturnsCookiesContext(): void
    {
        $cookies = new StubInputContext();
        $request = $this->makeRequest(
            cookies: $cookies,
        );

        self::assertSame($cookies, $request->input(InputContext::COOKIE));
    }

    /**
     * @return \Generator<array{0: string|null, 1: non-empty-array<string>, 2: string|null}>
     */
    public static function prefersDataProvider(): \Generator
    {
        yield [
            null,
            [
                'text/html',
            ],
            'text/html',
        ];

        yield [
            '*/*',
            [
                'text/html',
                'application/json',
            ],
            'text/html',
        ];

        yield [
            'text/html',
            [
                'text/html',
            ],
            'text/html',
        ];

        yield [
            'TEXT/HTML',
            [
                'text/html',
            ],
            'text/html',
        ];

        yield [
            'text/*',
            [
                'text/html',
                'application/json',
            ],
            'text/html',
        ];

        yield [
            'image/*',
            [
                'text/html',
            ],
            null,
        ];

        yield [
            'text/html;q=0',
            [
                'text/html',
            ],
            null,
        ];

        yield [
            'text/html;q=0.5, application/json;q=0.9',
            [
                'text/html',
                'application/json',
            ],
            'application/json',
        ];

        yield [
            'image/png',
            [
                'text/html',
            ],
            null,
        ];
    }

    /**
     * @param non-empty-array<string> $supported
     */
    #[DataProvider('prefersDataProvider')]
    public function testPrefers(
        ?string $acceptHeader,
        array $supported,
        ?string $expected,
    ): void {
        $headers = $acceptHeader !== null
            ? new StubHeaderContext(
                [
                    'Accept' => $acceptHeader,
                ],
            )
            : new StubHeaderContext();

        self::assertSame($expected, $this->makeRequest(headers: $headers)->prefers(...$supported));
    }

    private function makeRequestWithAcceptHeader(
        ?string $acceptHeader,
    ): Request {
        $headers = $acceptHeader !== null
            ? new StubHeaderContext(
                [
                    'Accept' => $acceptHeader,
                ],
            )
            : new StubHeaderContext();

        return $this->makeRequest(
            headers: $headers,
        );
    }

    public function testAcceptsReturnsTrueWhenNoAcceptHeaderPresent(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader(null)->accepts('application/json'),
        );
    }

    public function testAcceptsReturnsTrueForWildcardAcceptHeader(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('*/*')->accepts('application/json'),
        );
    }

    public function testAcceptsReturnsTrueForExactMatch(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('application/json')->accepts('application/json'),
        );
    }

    public function testAcceptsReturnsTrueForTypeWildcardMatch(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('application/*')->accepts('application/json'),
        );
    }

    public function testAcceptsReturnsFalseForNonMatchingType(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('text/html')->accepts('application/json'),
        );
    }

    public function testAcceptsReturnsFalseWhenWeightedZero(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('application/json;q=0')->accepts('application/json'),
        );
    }

    public function testAcceptsAnyReturnsFalseForZeroArguments(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('*/*')->acceptsAny(),
        );
    }

    public function testAcceptsAnyReturnsTrueWhenAnyMimeMatches(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('text/html')->acceptsAny('application/json', 'text/html'),
        );
    }

    public function testAcceptsAnyReturnsFalseWhenNoMimesMatch(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('image/png')->acceptsAny('application/json', 'text/html'),
        );
    }

    public function testAcceptsAnyReturnsTrueWhenNoAcceptHeaderPresent(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader(null)->acceptsAny('application/json', 'text/html'),
        );
    }

    public function testAcceptsJsonReturnsTrueForApplicationJson(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('application/json')->acceptsJson(),
        );
    }

    public function testAcceptsJsonReturnsFalseForOtherType(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('text/html')->acceptsJson(),
        );
    }

    public function testAcceptsHtmlReturnsTrueForTextHtml(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('text/html')->acceptsHtml(),
        );
    }

    public function testAcceptsHtmlReturnsFalseForOtherType(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('application/json')->acceptsHtml(),
        );
    }

    public function testAcceptsCsvReturnsTrueForTextCsv(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('text/csv')->acceptsCsv(),
        );
    }

    public function testAcceptsCsvReturnsFalseForOtherType(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('application/json')->acceptsCsv(),
        );
    }

    public function testAcceptsXmlReturnsTrueForApplicationXml(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('application/xml')->acceptsXml(),
        );
    }

    public function testAcceptsXmlReturnsFalseForOtherType(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('text/html')->acceptsXml(),
        );
    }

    public function testAcceptsTextReturnsTrueForTextPlain(): void
    {
        self::assertTrue(
            $this->makeRequestWithAcceptHeader('text/plain')->acceptsText(),
        );
    }

    public function testAcceptsTextReturnsFalseForOtherType(): void
    {
        self::assertFalse(
            $this->makeRequestWithAcceptHeader('application/json')->acceptsText(),
        );
    }

    public function testIsNotModifiedReturnsFalseWithoutAnyConditionalHeaders(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(),
        );

        self::assertFalse(
            $request->isNotModified(
                etag: 'abc123',
                lastModified: new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('UTC')),
            ),
        );
    }

    public function testIsNotModifiedReturnsFalseWhenNullArgsProvided(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => '"abc123"',
                ],
            ),
        );

        self::assertFalse($request->isNotModified());
    }

    public function testIsNotModifiedReturnsTrueWhenIfNoneMatchMatchesExactly(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => '"abc123"',
                ],
            ),
        );

        self::assertTrue(
            $request->isNotModified(
                etag: 'abc123',
            ),
        );
    }

    public function testIsNotModifiedReturnsTrueWhenIfNoneMatchIsWildcard(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => '*',
                ],
            ),
        );

        self::assertTrue(
            $request->isNotModified(
                etag: 'anything',
            ),
        );
    }

    public function testIsNotModifiedReturnsTrueWhenEtagInMultiValueList(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => '"foo", "abc123", "bar"',
                ],
            ),
        );

        self::assertTrue(
            $request->isNotModified(
                etag: 'abc123',
            ),
        );
    }

    public function testIsNotModifiedReturnsTrueForWeakIfNoneMatchValue(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => 'W/"abc123"',
                ],
            ),
        );

        self::assertTrue(
            $request->isNotModified(
                etag: 'abc123',
            ),
        );
    }

    public function testIsNotModifiedReturnsFalseWhenEtagNotInList(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => '"foo", "bar"',
                ],
            ),
        );

        self::assertFalse(
            $request->isNotModified(
                etag: 'abc123',
            ),
        );
    }

    public function testIsNotModifiedReturnsFalseWhenIfNoneMatchEntryIsEmpty(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => ', , "xyz"',
                ],
            ),
        );

        self::assertFalse(
            $request->isNotModified(
                etag: 'abc123',
            ),
        );
    }

    public function testIsNotModifiedReturnsTrueWhenIfModifiedSinceIsAtOrAfterLastModified(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-Modified-Since' => 'Sat, 03 Jan 2026 12:00:00 GMT',
                ],
            ),
        );

        self::assertTrue(
            $request->isNotModified(
                lastModified: new \DateTimeImmutable('2026-01-01 12:00:00', new \DateTimeZone('UTC')),
            ),
        );
    }

    public function testIsNotModifiedReturnsTrueWhenIfModifiedSinceEqualsLastModified(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-Modified-Since' => 'Thu, 01 Jan 2026 12:00:00 GMT',
                ],
            ),
        );

        self::assertTrue(
            $request->isNotModified(
                lastModified: new \DateTimeImmutable('2026-01-01 12:00:00', new \DateTimeZone('UTC')),
            ),
        );
    }

    public function testIsNotModifiedReturnsFalseWhenIfModifiedSinceIsBeforeLastModified(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-Modified-Since' => 'Thu, 01 Jan 2026 00:00:00 GMT',
                ],
            ),
        );

        self::assertFalse(
            $request->isNotModified(
                lastModified: new \DateTimeImmutable('2026-01-02 00:00:00', new \DateTimeZone('UTC')),
            ),
        );
    }

    public function testIsNotModifiedReturnsFalseWhenIfModifiedSinceIsMalformed(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-Modified-Since' => 'not a real date',
                ],
            ),
        );

        self::assertFalse(
            $request->isNotModified(
                lastModified: new \DateTimeImmutable('2026-01-01', new \DateTimeZone('UTC')),
            ),
        );
    }

    public function testIsNotModifiedPrefersIfNoneMatchOverIfModifiedSince(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => '"different"',
                    'If-Modified-Since' => 'Sat, 03 Jan 2026 12:00:00 GMT',
                ],
            ),
        );

        self::assertFalse(
            $request->isNotModified(
                etag: 'abc123',
                lastModified: new \DateTimeImmutable('2026-01-01 12:00:00', new \DateTimeZone('UTC')),
            ),
        );
    }

    public function testIsNotModifiedFallsBackToLastModifiedWhenEtagAbsent(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-Modified-Since' => 'Sat, 03 Jan 2026 12:00:00 GMT',
                ],
            ),
        );

        self::assertTrue(
            $request->isNotModified(
                etag: 'abc123',
                lastModified: new \DateTimeImmutable('2026-01-01 12:00:00', new \DateTimeZone('UTC')),
            ),
        );
    }

    public function testIsModifiedIsInverseOfIsNotModified(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(
                [
                    'If-None-Match' => '"abc123"',
                ],
            ),
        );

        self::assertFalse(
            $request->isModified(
                etag: 'abc123',
            ),
        );
    }

    public function testIsModifiedReturnsTrueWhenNoConditionalHeadersPresent(): void
    {
        $request = $this->makeRequest(
            headers: new StubHeaderContext(),
        );

        self::assertTrue(
            $request->isModified(
                etag: 'abc123',
            ),
        );
    }

    public function testConstructorAcceptsMethodAsString(): void
    {
        $request = new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: 'POST',
        );

        self::assertSame(Method::POST, $request->method);
    }

    public function testConstructorPopulatesUriWithQueryStringWhenPresent(): void
    {
        $previous = $_SERVER;
        $_SERVER['REQUEST_URI'] = '/articles';

        try {
            $request = new Request(
                headers: new StubHeaderContext(),
                cookies: new StubInputContext(),
                get: new StubInputContext(),
                post: new StubInputContext(),
                files: new StubUploadedFilesContext(),
                body: new StubBodyContext(),
                queryString: 'id=42',
            );

            self::assertSame('/articles?id=42', $request->uri);
        } finally {
            $_SERVER = $previous;
        }
    }

    public function testConstructorParsesProtocolVersionFromServerProtocol(): void
    {
        $previous = $_SERVER;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2.0';

        try {
            $request = new Request(
                headers: new StubHeaderContext(),
                cookies: new StubInputContext(),
                get: new StubInputContext(),
                post: new StubInputContext(),
                files: new StubUploadedFilesContext(),
                body: new StubBodyContext(),
            );

            self::assertSame(HttpVersion::V2_0, $request->protocolVersion);
        } finally {
            $_SERVER = $previous;
        }
    }

    public function testConstructorFallsBackToHttp11WhenServerProtocolIsUnparseable(): void
    {
        $previous = $_SERVER;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/9999';

        try {
            $request = new Request(
                headers: new StubHeaderContext(),
                cookies: new StubInputContext(),
                get: new StubInputContext(),
                post: new StubInputContext(),
                files: new StubUploadedFilesContext(),
                body: new StubBodyContext(),
            );

            self::assertSame(HttpVersion::V1_1, $request->protocolVersion);
        } finally {
            $_SERVER = $previous;
        }
    }

    public function testConstructorReadsPortFromServerPortWhenPresent(): void
    {
        $previous = $_SERVER;
        $_SERVER['SERVER_PORT'] = '8443';

        try {
            $request = new Request(
                headers: new StubHeaderContext(),
                cookies: new StubInputContext(),
                get: new StubInputContext(),
                post: new StubInputContext(),
                files: new StubUploadedFilesContext(),
                body: new StubBodyContext(),
            );

            self::assertSame(8443, $request->port);
        } finally {
            $_SERVER = $previous;
        }
    }

    public function testWithMethodAcceptsMethodEnum(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withMethod(Method::POST);

        self::assertNotSame($request, $updated);
        self::assertSame(Method::POST, $updated->method);
    }

    public function testWithMethodAcceptsStringAndConverts(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withMethod('DELETE');

        self::assertSame(Method::DELETE, $updated->method);
    }

    public function testWithPathReturnsNewInstanceWithUpdatedPath(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withPath('/rewritten');

        self::assertNotSame($request, $updated);
        self::assertSame('/rewritten', $updated->path);
    }

    public function testWithUriReturnsNewInstanceWithUpdatedUri(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withUri('/rewritten?token=abc');

        self::assertNotSame($request, $updated);
        self::assertSame('/rewritten?token=abc', $updated->uri);
    }

    public function testWithQueryStringReturnsNewInstanceWithUpdatedQueryString(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withQueryString('foo=bar&baz=qux');

        self::assertNotSame($request, $updated);
        self::assertSame('foo=bar&baz=qux', $updated->queryString);
    }

    public function testWithProtocolVersionReturnsNewInstanceWithUpdatedProtocolVersion(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withProtocolVersion(HttpVersion::V2_0);

        self::assertNotSame($request, $updated);
        self::assertSame(HttpVersion::V2_0, $updated->protocolVersion);
    }

    public function testWithHttpsReturnsNewInstanceWithUpdatedHttps(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withHttps(true);

        self::assertNotSame($request, $updated);
        self::assertTrue($updated->https);
    }

    public function testWithHostReturnsNewInstanceWithUpdatedHost(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withHost('example.com');

        self::assertNotSame($request, $updated);
        self::assertSame('example.com', $updated->host);
    }

    public function testWithPortReturnsNewInstanceWithUpdatedPort(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withPort(8443);

        self::assertNotSame($request, $updated);
        self::assertSame(8443, $updated->port);
    }

    public function testWithIpAddressReturnsNewInstanceWithUpdatedIpAddress(): void
    {
        $request = $this->makeRequest();
        $updated = $request->withIpAddress('203.0.113.42');

        self::assertNotSame($request, $updated);
        self::assertSame('203.0.113.42', $updated->ipAddress);
    }
}
