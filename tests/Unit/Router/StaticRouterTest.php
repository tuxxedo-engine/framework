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
        string $path = '/test',
        string $controller = self::class,
        string $action = 'index',
        ?string $name = null,
        RoutePriority $priority = RoutePriority::NORMAL,
        ?string $regexPath = null,
    ): Route {
        return new Route(
            method: $method,
            path: $path,
            controller: $controller,
            action: $action,
            name: $name,
            priority: $priority,
            regexPath: $regexPath,
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
            path: '/cold',
            priority: RoutePriority::COLD,
        );

        $normal = $this->makeRoute(
            path: '/normal',
        );

        $hot = $this->makeRoute(
            path: '/hot',
            priority: RoutePriority::HOT,
        );

        $router = StaticRouter::createPriorityBased(
            routes: [
                $cold,
                $normal,
                $hot,
            ],
        );

        $paths = \array_map(
            static fn (RouteInterface $route): string => $route->path,
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
            $paths,
        );
    }

    public function testCreatePriorityBasedReturnsStaticRouter(): void
    {
        $router = StaticRouter::createPriorityBased(
            routes: [],
        );

        self::assertInstanceOf(StaticRouter::class, $router);
    }

    public function testFindByPathReturnsNullWhenNoRouteMatches(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    path: '/foo',
                ),
            ],
        );

        self::assertNull(
            $router->findByPath(
                method: Method::GET,
                path: '/missing',
            ),
        );
    }

    public function testFindByPathReturnsNullWhenRouterIsEmpty(): void
    {
        $router = $this->makeRouter(
            routes: [],
        );

        self::assertNull(
            $router->findByPath(
                method: Method::GET,
                path: '/anything',
            ),
        );
    }

    public function testFindByPathMatchesPlainPathExactly(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            path: '/users',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByPath(
            method: Method::GET,
            path: '/users',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
        self::assertSame([], $dispatchable->arguments);
    }

    public function testFindByPathDoesNotPartialMatchPlainPath(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    path: '/users',
                ),
            ],
        );

        self::assertNull(
            $router->findByPath(
                method: Method::GET,
                path: '/users/42',
            ),
        );
    }

    public function testFindByPathUsesRegexPathWhenProvided(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            path: '/users/{id}',
            regexPath: '#^/users/(?<id>\d+)$#',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByPath(
            method: Method::GET,
            path: '/users/42',
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

    public function testFindByPathRegexFallsThroughOnNoMatch(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    path: '/users/{id}',
                    regexPath: '#^/users/(?<id>\d+)$#',
                ),
            ],
        );

        self::assertNull(
            $router->findByPath(
                method: Method::GET,
                path: '/users/abc',
            ),
        );
    }

    public function testFindByPathThrowsMethodNotAllowedWhenOnlyMethodMismatches(): void
    {
        $router = $this->makeRouter(
            routes: [
                $this->makeRoute(
                    method: Method::GET,
                    path: '/users',
                ),
            ],
        );

        self::expectException(HttpException::class);

        $router->findByPath(
            method: Method::POST,
            path: '/users',
        );
    }

    public function testFindByPathPrefersMatchingRouteOverMethodMismatch(): void
    {
        $getRoute = $this->makeRoute(
            method: Method::GET,
            path: '/users',
        );

        $postRoute = $this->makeRoute(
            method: Method::POST,
            path: '/users',
        );

        $router = $this->makeRouter(
            routes: [
                $getRoute,
                $postRoute,
            ],
        );

        $dispatchable = $router->findByPath(
            method: Method::POST,
            path: '/users',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($postRoute, $dispatchable->route);
    }

    public function testFindByPathMatchesRouteWithoutMethodRestriction(): void
    {
        $route = $this->makeRoute(
            path: '/any',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByPath(
            method: Method::DELETE,
            path: '/any',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
    }

    public function testFindByPathAcceptsMethodAsString(): void
    {
        $route = $this->makeRoute(
            method: Method::POST,
            path: '/users',
        );

        $router = $this->makeRouter(
            routes: [
                $route,
            ],
        );

        $dispatchable = $router->findByPath(
            method: 'POST',
            path: '/users',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame($route, $dispatchable->route);
    }

    public function testFindByPathReturnsFirstMatchingRoute(): void
    {
        $first = $this->makeRoute(
            method: Method::GET,
            path: '/users',
            action: 'first',
        );

        $second = $this->makeRoute(
            method: Method::GET,
            path: '/users',
            action: 'second',
        );

        $router = $this->makeRouter(
            routes: [
                $first,
                $second,
            ],
        );

        $dispatchable = $router->findByPath(
            method: Method::GET,
            path: '/users',
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
                    path: '/users',
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
            path: '/users',
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
            path: '/users/{id}',
            name: 'users.show',
            regexPath: '#^/users/(?<id>\d+)$#',
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
                    path: '/users',
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
            path: '/users',
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

    public function testFindByRequestDelegatesToFindByPath(): void
    {
        $route = $this->makeRoute(
            method: Method::GET,
            path: '/users',
        );

        $request = new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: Method::GET,
            path: '/users',
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
            path: '/users',
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
