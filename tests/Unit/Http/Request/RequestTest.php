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
use Support\Http\Request\Context\StubServerContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Router\StubDispatchableRoute;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Context\ServerContextInterface;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Router\DispatchableRouteInterface;

class RequestTest extends TestCase
{
    private function makeRequest(
        ?DispatchableRouteInterface $route = null,
        ?ServerContextInterface $server = null,
        ?HeaderContextInterface $headers = null,
        ?InputContextInterface $cookies = null,
        ?InputContextInterface $get = null,
        ?InputContextInterface $post = null,
        ?UploadedFilesContextInterface $files = null,
        ?BodyContextInterface $body = null,
    ): Request {
        return new Request(
            route: $route,
            server: $server ?? new StubServerContext(),
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

        self::assertNotSame($request, $request->withRoute(new StubDispatchableRoute()));
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
        $server = new StubServerContext();
        $headers = new StubHeaderContext();
        $cookies = new StubInputContext();
        $get = new StubInputContext();
        $post = new StubInputContext();
        $files = new StubUploadedFilesContext();
        $body = new StubBodyContext();

        $new = $this->makeRequest(
            server: $server,
            headers: $headers,
            cookies: $cookies,
            get: $get,
            post: $post,
            files: $files,
            body: $body,
        )->withRoute(
            route: new StubDispatchableRoute(),
        );

        self::assertSame($server, $new->server);
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
    public static function negotiateDataProvider(): \Generator
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
    #[DataProvider('negotiateDataProvider')]
    public function testNegotiate(
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

        self::assertSame($expected, $this->makeRequest(headers: $headers)->negotiate($supported));
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
}
