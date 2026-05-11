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

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubServerContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouterException;
use Tuxxedo\Router\StaticRouter;
use Tuxxedo\View\Lumi\Library\Standard\Function\RouteFunction;

class RouteFunctionTest extends TestCase
{
    private function makeContainerWithNamedRoute(
        string $name,
        string $uri,
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

    private function makeContainerWithCurrentRoute(
        string $uri,
    ): Container {
        $request = (new Request(
            server: new StubServerContext(),
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        ))->withRoute(
            new DispatchableRoute(
                route: new Route(
                    method: null,
                    uri: $uri,
                    controller: self::class,
                    action: 'index',
                ),
            ),
        );

        return (new Container())->persistent(
            class: $request,
        );
    }

    public function testCallReturnsUrlForNamedRoute(): void
    {
        $function = new RouteFunction(
            container: $this->makeContainerWithNamedRoute('home', '/home'),
        );

        self::assertSame(
            '/home',
            $function->call(
                [
                    'home',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallThrowsForUnknownNamedRoute(): void
    {
        $this->expectException(RouterException::class);

        (new RouteFunction(
            container: $this->makeContainerWithNamedRoute('home', '/home'),
        ))->call(
            [
                'missing',
            ],
            static fn () => new StubRuntimeContext(),
        );
    }

    public function testCallReturnsUrlForCurrentRoute(): void
    {
        $function = new RouteFunction(
            container: $this->makeContainerWithCurrentRoute('/home'),
        );

        self::assertSame(
            '/home',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsUrlForCurrentRouteWithNewArguments(): void
    {
        $function = new RouteFunction(
            container: $this->makeContainerWithCurrentRoute('/home'),
        );

        self::assertSame(
            '/home',
            $function->call(
                [
                    [
                        'extra' => 'value',
                    ],
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
