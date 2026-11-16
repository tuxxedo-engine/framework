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

use Fixture\Router\RouteDiscoverer\Discovery\Consumer\ConsumerController;
use Fixture\Router\RouteDiscoverer\Discovery\Middleware\MiddlewareController;
use Fixture\Router\RouteDiscoverer\Discovery\MultiMethod\MultiMethodController;
use Fixture\Router\RouteDiscoverer\Discovery\PrefixDefault\PrefixDefaultController;
use Fixture\Router\RouteDiscoverer\Discovery\RouteStringPrefix\RoutePrefixController;
use Fixture\Router\RouteDiscoverer\Discovery\StickyPrefix\StickyPrefixController;
use Fixture\Router\RouteDiscoverer\Discovery\TrailingSlash\TrailingController;
use Fixture\Router\RouteDiscoverer\Support\TestPrefixWithDefaults;
use Fixture\Router\RouteDiscoverer\Support\TestStickyPrefix;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Method;
use Tuxxedo\Router\Builder\RouteBuilder;
use Tuxxedo\Router\RouteDiscoverer;
use Tuxxedo\Router\RouteInterface;

class RouteParityTest extends TestCase
{
    private const string FIXTURE_NAMESPACE_BASE = 'Fixture\\Router\\RouteDiscoverer\\Discovery\\';

    /**
     * @return \Generator<string, array{0: string, 1: \Closure(RouteBuilder): RouteBuilder}>
     */
    public static function providesParityCases(): \Generator
    {
        yield 'argument consumer' => [
            'Consumer',
            static fn (RouteBuilder $builder): RouteBuilder => $builder->get(
                path: '/users/{id}',
                controller: ConsumerController::class,
                action: 'show',
            ),
        ];

        yield 'prefix with defaults' => [
            'PrefixDefault',
            static fn (RouteBuilder $builder): RouteBuilder => $builder->get(
                path: '/home',
                controller: PrefixDefaultController::class,
                action: 'home',
                prefix: TestPrefixWithDefaults::class,
            ),
        ];

        yield 'sticky prefix' => [
            'StickyPrefix',
            static fn (RouteBuilder $builder): RouteBuilder => $builder->get(
                path: '/home',
                controller: StickyPrefixController::class,
                action: 'home',
                prefix: TestStickyPrefix::class,
            ),
        ];

        yield 'string prefix' => [
            'RouteStringPrefix',
            static fn (RouteBuilder $builder): RouteBuilder => $builder->get(
                path: '/users',
                controller: RoutePrefixController::class,
                action: 'users',
                prefix: '/api',
            ),
        ];

        yield 'middleware attributes' => [
            'Middleware',
            static fn (RouteBuilder $builder): RouteBuilder => $builder
                ->get(
                    path: '/protected',
                    controller: MiddlewareController::class,
                    action: 'protected',
                )
                ->get(
                    path: '/open',
                    controller: MiddlewareController::class,
                    action: 'open',
                ),
        ];

        yield 'several methods' => [
            'MultiMethod',
            static fn (RouteBuilder $builder): RouteBuilder => $builder->match(
                methods: [
                    Method::GET,
                    Method::POST,
                ],
                path: '/contact',
                controller: MultiMethodController::class,
                action: 'contact',
            ),
        ];

        yield 'trailing slash' => [
            'TrailingSlash',
            static fn (RouteBuilder $builder): RouteBuilder => $builder->get(
                path: '/page',
                controller: TrailingController::class,
                action: 'page',
                trailingSlash: true,
            ),
        ];
    }

    /**
     * @param \Closure(RouteBuilder): RouteBuilder $register
     */
    #[DataProvider('providesParityCases')]
    public function testDiscoveredAndBuiltRoutesAreEqual(
        string $scenario,
        \Closure $register,
    ): void {
        $container = new Container();
        $discoverer = new RouteDiscoverer(
            container: $container,
            baseNamespace: self::FIXTURE_NAMESPACE_BASE . $scenario . '\\',
            directory: \str_replace('\\', '/', \dirname(__DIR__, 2)) . '/Fixture/Router/RouteDiscoverer/Discovery/' . $scenario,
            strictMode: true,
        );

        $discovered = \iterator_to_array(
            $discoverer->discover(),
            preserve_keys: false,
        );

        $built = $register(
            new RouteBuilder(
                container: $container,
            ),
        )->build();

        self::assertNotSame(
            [],
            $discovered,
        );
        self::assertEquals(
            \array_map(self::describe(...), $discovered),
            \array_map(self::describe(...), $built),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function describe(
        RouteInterface $route,
    ): array {
        return [
            'method' => $route->method,
            'path' => $route->path,
            'controller' => $route->controller,
            'action' => $route->action,
            'name' => $route->name,
            'priority' => $route->priority,
            'regexPath' => $route->regexPath,
            'arguments' => $route->arguments,
            'stickyPrefix' => $route->stickyPrefix,
            'middleware' => \array_map(
                static fn (\Closure $middleware): string => $middleware()::class,
                $route->middleware,
            ),
        ];
    }
}
