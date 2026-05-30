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

namespace Unit\Router;

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Router\DispatchableRouteInterface;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouteInterface;
use Tuxxedo\Router\RoutePriority;
use Tuxxedo\Router\StaticRouter;

class StaticRouterTest extends TestCase
{
    /**
     * @param class-string $controller
     */
    private function makeRoute(
        Method|string|null $method = null,
        string $uri = '/test',
        string $controller = self::class,
        string $action = 'index',
        ?string $name = null,
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $regexUri = null,
    ): Route {
        return new Route(
            method: $method,
            uri: $uri,
            controller: $controller,
            action: $action,
            name: $name,
            priority: $priority,
            regexUri: $regexUri,
        );
    }

    /**
     * @param RouteInterface[] $routes
     */
    private function makeRouter(
        array $routes,
    ): StaticRouter {
        return new StaticRouter(
            routes: $routes,
        );
    }

    public function testConstructorExposesProvidedRoutes(): void
    {
        $route = $this->makeRoute();
        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        self::assertSame(
            [
                $route,
            ],
            $router->routes,
        );
    }

    public function testGetRoutesReturnsProvidedRoutes(): void
    {
        $route = $this->makeRoute();
        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        self::assertSame(
            [
                $route,
            ],
            \iterator_to_array(
                $router->getRoutes(),
                preserve_keys: false,
            ),
        );
    }

    public function testGetRoutesIsEmptyForEmptyRouter(): void
    {
        $router = $this->makeRouter(
            routes: [],
        );

        self::assertSame(
            [],
            \iterator_to_array(
                $router->getRoutes(),
                preserve_keys: false,
            ),
        );
    }

    public function testCreatePriorityBasedOrdersByPriority(): void
    {
        $cold = $this->makeRoute(
            uri: '/cold',
            priority: RoutePriority::COLD,
        );

        $normal = $this->makeRoute(
            uri: '/normal',
        );

        $hot = $this->makeRoute(
            uri: '/hot',
            priority: RoutePriority::HOT,
        );

        $router = StaticRouter::createPriorityBased(
            routes: [
                $cold,
                $normal,
                $hot,
            ],
        );

        $uris = \array_map(
            static fn (RouteInterface $route): string => $route->uri,
            \array_values(
                \iterator_to_array(
                    $router->getRoutes(),
                ),
            ),
        );

        self::assertSame(
            [
                '/hot',
                '/normal',
                '/cold',
            ],
            $uris,
        );
    }

    public function testCreatePriorityBasedReturnsStaticRouter(): void
    {
        $router = StaticRouter::createPriorityBased(
            routes: [],
        );

        self::assertInstanceOf(StaticRouter::class, $router);
    }

    public function testFindByUriReturnsNullWhenNoRouteMatches(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    uri: '/foo',
                ),
            ],
        );

        self::assertNull(
            $router->findByUri(
                method: Method::GET,
                uri: '/missing',
            ),
        );
    }

    public function testFindByUriReturnsNullWhenRouterIsEmpty(): void
    {
        $router = $this->makeRouter(
            routes: [],
        );

        self::assertNull(
            $router->findByUri(
                method: Method::GET,
                uri: '/anything',
            ),
        );
    }

    public function testFindByUriMatchesPlainUriExactly(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            uri: '/users',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByUri(
            method: Method::GET,
            uri: '/users',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
        self::assertSame([], $dispatchable->arguments);
    }

    public function testFindByUriDoesNotPartialMatchPlainUri(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    uri: '/users',
                ),
            ],
        );

        self::assertNull(
            $router->findByUri(
                method: Method::GET,
                uri: '/users/42',
            ),
        );
    }

    public function testFindByUriUsesRegexUriWhenProvided(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            uri: '/users/{id}',
            regexUri: '#^/users/(?<id>\d+)$#',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByUri(
            method: Method::GET,
            uri: '/users/42',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
        self::assertSame(
            [
                'id' => '42',
            ],
            $dispatchable->arguments,
        );
    }

    public function testFindByUriRegexFallsThroughOnNoMatch(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    uri: '/users/{id}',
                    regexUri: '#^/users/(?<id>\d+)$#',
                ),
            ],
        );

        self::assertNull(
            $router->findByUri(
                method: Method::GET,
                uri: '/users/abc',
            ),
        );
    }

    public function testFindByUriThrowsMethodNotAllowedWhenOnlyMethodMismatches(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    uri: '/users',
                ),
            ],
        );

        self::expectException(HttpException::class);

        $router->findByUri(
            method: Method::POST,
            uri: '/users',
        );
    }

    public function testFindByUriPrefersMatchingRouteOverMethodMismatch(): void
    {
        $getRoute = $this->makeRoute(
            method: Method::GET,
            uri: '/users',
        );

        $postRoute = $this->makeRoute(
            method: Method::POST,
            uri: '/users',
        );

        $router = $this->makeRouter(
            routes: [
                $getRoute,
                $postRoute,
            ],
        );

        $dispatchable = $router->findByUri(
            method: Method::POST,
            uri: '/users',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($postRoute, $dispatchable->route);
    }

    public function testFindByUriMatchesRouteWithoutMethodRestriction(): void
    {
        $route = $this->makeRoute(
            uri: '/any',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByUri(
            method: Method::DELETE,
            uri: '/any',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
    }

    public function testFindByUriAcceptsMethodAsString(): void
    {
        $route = $this->makeRoute(
            method: Method::POST,
            uri: '/users',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByUri(
            method: 'POST',
            uri: '/users',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
    }

    public function testFindByUriReturnsFirstMatchingRoute(): void
    {
        $first = $this->makeRoute(
            method: Method::GET,
            uri: '/users',
            action: 'first',
        );

        $second = $this->makeRoute(
            method: Method::GET,
            uri: '/users',
            action: 'second',
        );

        $router = $this->makeRouter(
            routes: [
                $first,
                $second,
            ],
        );

        $dispatchable = $router->findByUri(
            method: Method::GET,
            uri: '/users',
        );

        self::assertNotNull($dispatchable);
        self::assertSame($first, $dispatchable->route);
    }

    public function testFindByNameReturnsNullWhenNoRouteMatches(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    uri: '/users',
                    name: 'users.index',
                ),
            ],
        );

        self::assertNull(
            $router->findByName(
                name: 'users.show',
            ),
        );
    }

    public function testFindByNameReturnsDispatchableForMatchingName(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            uri: '/users',
            name: 'users.index',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByName(
            name: 'users.index',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
    }

    public function testFindByNameAttachesProvidedArguments(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            uri: '/users/{id}',
            name: 'users.show',
            regexUri: '#^/users/(?<id>\d+)$#',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByName(
            name: 'users.show',
            arguments: [
                'id' => '42',
            ],
        );

        self::assertNotNull($dispatchable);
        self::assertSame(
            [
                'id' => '42',
            ],
            $dispatchable->arguments,
        );
    }

    public function testFindByNameThrowsMethodNotAllowedWhenMethodMismatches(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    uri: '/users',
                    name: 'users.index',
                ),
            ],
        );

        self::expectException(HttpException::class);

        $router->findByName(
            name: 'users.index',
            method: Method::POST,
        );
    }

    public function testFindByNameAcceptsMethodAsString(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            uri: '/users',
            name: 'users.index',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByName(
            name: 'users.index',
            method: 'GET',
        );

        self::assertNotNull($dispatchable);
        self::assertSame($route, $dispatchable->route);
    }

    public function testFindByRequestDelegatesToFindByUri(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            uri: '/users',
        );

        $request = new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: Method::GET,
            uri: '/users',
        );

        $dispatchable = $this->makeRouter(
            routes: [
                $route,
            ],
        )->findByRequest($request);

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
    }

    public function testFindByNameIgnoresMethodWhenNotProvided(): void
    {
        $route = $this->makeRoute(
            method: Method::POST,
            uri: '/users',
            name: 'users.create',
        );
        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByName(
            name: 'users.create',
        );

        self::assertNotNull($dispatchable);
        self::assertSame($route, $dispatchable->route);
    }
}
