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
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Router\DispatchableRouteInterface;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Router\RouteDiscoverer;
use Tuxxedo\Router\RouteInterface;

class DynamicRouterTest extends TestCase
{
    private const string FIXTURE_NAMESPACE_BASE = 'Fixture\\Router\\RouteDiscoverer\\Discovery\\';

    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    private function fixtureDir(
        string $scenario,
    ): string {
        $base = \str_replace('\\', '/', \dirname(__DIR__, 2)) . '/Fixture/Router/RouteDiscoverer/Discovery';

        return $base . '/' . $scenario;
    }

    private function createDiscoverer(
        string $scenario,
        bool $strictMode = false,
    ): RouteDiscoverer {
        return new RouteDiscoverer(
            container: $this->container,
            baseNamespace: self::FIXTURE_NAMESPACE_BASE . $scenario . '\\',
            directory: $this->fixtureDir($scenario),
            strictMode: $strictMode,
        );
    }

    private function createFromDirectory(
        string $scenario,
        bool $strictMode = false,
    ): DynamicRouter {
        return DynamicRouter::createFromDirectory(
            container: $this->container,
            directory: $this->fixtureDir($scenario),
            baseNamespace: self::FIXTURE_NAMESPACE_BASE . $scenario . '\\',
            strictMode: $strictMode,
        );
    }

    public function testCreateFromDirectoryReturnsDynamicRouter(): void
    {
        $router = $this->createFromDirectory('Simple');

        self::assertInstanceOf(DynamicRouter::class, $router);
    }

    public function testCreateFromDirectoryConfiguresDiscovererWithProvidedSettings(): void
    {
        $router = DynamicRouter::createFromDirectory(
            container: $this->container,
            directory: $this->fixtureDir('Simple'),
            baseNamespace: 'App\\',
            strictMode: true,
        );

        self::assertInstanceOf(RouteDiscoverer::class, $router->discoverer);
        self::assertSame('App\\', $router->discoverer->baseNamespace);
        self::assertSame($this->fixtureDir('Simple'), $router->discoverer->directory);
        self::assertTrue($router->discoverer->strictMode);
    }

    public function testCreateFromDirectoryDefaultsStrictModeToFalse(): void
    {
        $router = DynamicRouter::createFromDirectory(
            container: $this->container,
            directory: $this->fixtureDir('Simple'),
            baseNamespace: 'App\\',
        );

        self::assertFalse($router->discoverer->strictMode);
    }

    public function testCreateFromDiscovererStoresProvidedDiscoverer(): void
    {
        $discoverer = $this->createDiscoverer('Simple');

        $router = DynamicRouter::createFromDiscoverer(
            discoverer: $discoverer,
        );

        self::assertSame($discoverer, $router->discoverer);
    }

    public function testGetRoutesYieldsDiscoveredRoutes(): void
    {
        $router = $this->createFromDirectory('Multiple');

        $paths = \array_map(
            static fn (RouteInterface $route): string => $route->path,
            \iterator_to_array(
                $router->getRoutes(),
                preserve_keys: false,
            ),
        );

        \sort($paths);

        self::assertSame(
            [
                '/alpha',
                '/beta',
            ],
            $paths,
        );
    }

    public function testGetRoutesYieldsNothingForEmptyDirectory(): void
    {
        $router = $this->createFromDirectory('Empty');

        self::assertSame(
            [],
            \iterator_to_array(
                $router->getRoutes(),
                preserve_keys: false,
            ),
        );
    }

    public function testFindByPathResolvesDiscoveredPlainRoute(): void
    {
        $router = $this->createFromDirectory('Simple');

        $dispatchable = $router->findByPath(
            method: Method::GET,
            path: '/home',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame('/home', $dispatchable->route->path);
        self::assertSame(Method::GET, $dispatchable->route->method);
        self::assertSame(
            self::FIXTURE_NAMESPACE_BASE . 'Simple\\SimpleController',
            $dispatchable->route->controller,
        );
        self::assertSame('home', $dispatchable->route->action);
    }

    public function testFindByPathResolvesDiscoveredRegexRouteAndExtractsArguments(): void
    {
        $router = $this->createFromDirectory('TypedImplicit');

        $dispatchable = $router->findByPath(
            method: Method::GET,
            path: '/users/42',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame(
            [
                'id' => '42',
            ],
            $dispatchable->arguments,
        );
    }

    public function testFindByPathReturnsNullWhenNoDiscoveredRouteMatches(): void
    {
        $router = $this->createFromDirectory('Simple');

        self::assertNull(
            $router->findByPath(
                method: Method::GET,
                path: '/nonexistent',
            ),
        );
    }

    public function testFindByPathThrowsMethodNotAllowedForKnownPathWithWrongMethod(): void
    {
        $router = $this->createFromDirectory('Simple');

        self::expectException(HttpException::class);

        $router->findByPath(
            method: Method::POST,
            path: '/home',
        );
    }

    public function testFindByNameResolvesDiscoveredNamedRoute(): void
    {
        $router = $this->createFromDirectory('IndexNamed');

        $dispatchable = $router->findByName(
            name: 'dashboard.home',
        );

        self::assertInstanceOf(DispatchableRouteInterface::class, $dispatchable);
        self::assertSame('dashboard.home', $dispatchable->route->name);
    }

    public function testFindByNameReturnsNullForUnknownName(): void
    {
        $router = $this->createFromDirectory('IndexNamed');

        self::assertNull(
            $router->findByName(
                name: 'unknown.route',
            ),
        );
    }
}
