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

namespace Unit\View\Lumi\Library\Standard\Function;

use Fixture\Router\RouteDiscoverer\Support\TestStickyPrefix;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\ArgumentNode;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouteArgument;
use Tuxxedo\Router\RouterException;
use Tuxxedo\Router\StaticRouter;
use Tuxxedo\View\Lumi\Library\Standard\Function\RequestFunctions;

class RequestFunctionsTest extends TestCase
{
    private function makeRequest(
        ?DispatchableRoute $route = null,
    ): Request {
        $request = new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        );

        if ($route !== null) {
            return $request->withRoute($route);
        }

        return $request;
    }

    private function makeFunctions(
        ?Request $request = null,
        ?StaticRouter $router = null,
        string $baseUrl = 'https://example.com/',
    ): RequestFunctions {
        return new RequestFunctions(
            request: $request ?? $this->makeRequest(),
            router: $router ?? new StaticRouter(
                routes: [],
            ),
            url: new Url($baseUrl),
        );
    }

    private function makeStickyRoute(
        string $name,
        string $path,
    ): Route {
        return new Route(
            method: null,
            path: $path,
            controller: self::class,
            action: 'index',
            name: $name,
            arguments: [
                new RouteArgument(
                    node: new ArgumentNode(
                        name: 'tenant',
                        kind: ArgumentKind::TYPED_IMPLICIT,
                        optional: true,
                        prefixed: true,
                    ),
                    mappedName: null,
                    nativeType: 'string',
                    allowsNull: false,
                    defaultValue: null,
                ),
            ],
            stickyPrefix: TestStickyPrefix::class,
        );
    }

    public function testRequestReturnsRequest(): void
    {
        $request = $this->makeRequest();

        self::assertSame(
            $request,
            $this->makeFunctions(
                request: $request,
            )->request(),
        );
    }

    public function testUrlReturnsFullUrl(): void
    {
        self::assertSame(
            'https://example.com/about',
            $this->makeFunctions()->url('about'),
        );
    }

    public function testUrlStripsLeadingSlashFromPath(): void
    {
        self::assertSame(
            'https://example.com/about',
            $this->makeFunctions()->url('/about'),
        );
    }

    public function testRouteReturnsUrlForNamedRoute(): void
    {
        $functions = $this->makeFunctions(
            router: new StaticRouter(
                routes: [
                    new Route(
                        method: null,
                        path: '/home',
                        controller: self::class,
                        action: 'index',
                        name: 'home',
                    ),
                ],
            ),
        );

        self::assertSame('/home', $functions->route('home'));
    }

    public function testRouteThrowsForUnknownNamedRoute(): void
    {
        self::expectException(RouterException::class);

        $this->makeFunctions()->route('missing');
    }

    public function testRouteReturnsUrlForCurrentRoute(): void
    {
        $functions = $this->makeFunctions(
            request: $this->makeRequest(
                route: new DispatchableRoute(
                    route: new Route(
                        method: null,
                        path: '/home',
                        controller: self::class,
                        action: 'index',
                    ),
                ),
            ),
        );

        self::assertSame('/home', $functions->route());
    }

    public function testRouteReturnsUrlForCurrentRouteWithNewArguments(): void
    {
        $functions = $this->makeFunctions(
            request: $this->makeRequest(
                route: new DispatchableRoute(
                    route: new Route(
                        method: null,
                        path: '/home',
                        controller: self::class,
                        action: 'index',
                    ),
                ),
            ),
        );

        self::assertSame(
            '/home',
            $functions->route(
                name: [
                    'extra' => 'value',
                ],
            ),
        );
    }

    public function testRouteKeepsStickyPrefixArgumentsForNamedRoute(): void
    {
        $billing = $this->makeStickyRoute(
            name: 'billing',
            path: '/{?tenant}/billing',
        );

        $functions = $this->makeFunctions(
            request: $this->makeRequest(
                route: new DispatchableRoute(
                    route: $this->makeStickyRoute(
                        name: 'home',
                        path: '/{?tenant}/home',
                    ),
                    arguments: [
                        'tenant' => 'acme',
                    ],
                ),
            ),
            router: new StaticRouter(
                routes: [
                    $billing,
                ],
            ),
        );

        self::assertSame('/acme/billing', $functions->route('billing'));
        self::assertSame(
            '/billing',
            $functions->route(
                name: 'billing',
                args: [
                    'tenant' => null,
                ],
            ),
        );
    }

    public function testRouteDropsArgumentPassedAsNullForCurrentRoute(): void
    {
        $functions = $this->makeFunctions(
            request: $this->makeRequest(
                route: new DispatchableRoute(
                    route: $this->makeStickyRoute(
                        name: 'home',
                        path: '/{?tenant}/home',
                    ),
                    arguments: [
                        'tenant' => 'acme',
                    ],
                ),
            ),
        );

        self::assertSame(
            '/home',
            $functions->route(
                name: [
                    'tenant' => null,
                ],
            ),
        );
    }
}
