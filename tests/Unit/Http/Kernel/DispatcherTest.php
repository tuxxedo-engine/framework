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

namespace Unit\Http\Kernel;

use Fixture\Http\Kernel\DispatcherController;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\Dispatcher;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\ArgumentNode;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouteArgument;

class DispatcherTest extends TestCase
{
    private function makeRequest(): RequestInterface
    {
        return new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        );
    }

    private function makeDispatchable(
        string $action,
    ): DispatchableRoute {
        return new DispatchableRoute(
            route: new Route(
                method: null,
                path: '/test',
                controller: DispatcherController::class,
                action: $action,
            ),
        );
    }

    public function testDispatchCallsControllerActionAndReturnsResponse(): void
    {
        $container = new Container();

        $response = (new Dispatcher())->dispatch(
            container: $container,
            dispatchableRoute: $this->makeDispatchable('index'),
            request: $this->makeRequest(),
        );

        self::assertSame('dispatched', $response->body);
    }

    public function testDispatchConvertsResponsableToResponse(): void
    {
        $container = new Container();

        $response = (new Dispatcher())->dispatch(
            container: $container,
            dispatchableRoute: $this->makeDispatchable('responsable'),
            request: $this->makeRequest(),
        );

        self::assertSame('from responsable', $response->body);
    }

    public function testDispatchThrowsWhenActionNotCallable(): void
    {
        $this->expectException(HttpException::class);

        (new Dispatcher())->dispatch(
            container: new Container(),
            dispatchableRoute: $this->makeDispatchable('nonExistentMethod'),
            request: $this->makeRequest(),
        );
    }

    public function testDispatchThrowsWhenControllerReturnsNonResponse(): void
    {
        $this->expectException(HttpException::class);

        (new Dispatcher())->dispatch(
            container: new Container(),
            dispatchableRoute: $this->makeDispatchable('returnsNull'),
            request: $this->makeRequest(),
        );
    }

    public function testDispatchPassesRouteArgumentsToController(): void
    {
        $dispatchable = new DispatchableRoute(
            route: new Route(
                method: null,
                path: '/users/{id}',
                controller: DispatcherController::class,
                action: 'show',
                arguments: [
                    new RouteArgument(
                        node: new ArgumentNode(
                            name: 'id',
                            kind: ArgumentKind::TYPED_EXPLICIT,
                        ),
                        mappedName: null,
                        nativeType: 'int',
                        allowsNull: false,
                        defaultValue: null,
                    ),
                ],
            ),
            arguments: [
                'id' => '42',
            ],
        );

        $response = (new Dispatcher())->dispatch(
            container: new Container(),
            dispatchableRoute: $dispatchable,
            request: $this->makeRequest(),
        );

        self::assertSame('42', $response->body);
    }
}
