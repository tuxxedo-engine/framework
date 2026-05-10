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
use Tuxxedo\Http\Method;
use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\ArgumentNode;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\DispatchableRouteInterface;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouteArgument;
use Tuxxedo\Router\RouteArgumentInterface;

class DispatchableRouteTest extends TestCase
{
    /**
     * @param RouteArgumentInterface[] $arguments
     */
    private function makeRoute(
        string $uri = '/home',
        array $arguments = [],
    ): Route {
        return new Route(
            method: Method::GET,
            uri: $uri,
            controller: static::class,
            action: 'index',
            arguments: $arguments,
        );
    }

    private function makeArgument(
        string $name,
        bool $optional = false,
        ?string $mappedName = null,
    ): RouteArgument {
        return new RouteArgument(
            node: new ArgumentNode(
                name: $name,
                kind: ArgumentKind::TYPED_IMPLICIT,
                optional: $optional,
            ),
            mappedName: $mappedName,
            nativeType: 'string',
            allowsNull: false,
            defaultValue: null,
        );
    }

    public function testConstructorExposesRoute(): void
    {
        $route = $this->makeRoute();

        $dispatchable = new DispatchableRoute(route: $route);

        self::assertSame($route, $dispatchable->route);
    }

    public function testConstructorExposesArguments(): void
    {
        $dispatchable = new DispatchableRoute(
            route: $this->makeRoute(),
            arguments: [
                'id' => '1',
            ],
        );

        self::assertSame(
            [
                'id' => '1',
            ],
            $dispatchable->arguments,
        );
    }

    public function testConstructorDefaultsArgumentsToEmptyArray(): void
    {
        $dispatchable = new DispatchableRoute(
            route: $this->makeRoute(),
        );

        self::assertSame([], $dispatchable->arguments);
    }

    public function testImplementsDispatchableRouteInterface(): void
    {
        self::assertInstanceOf(
            DispatchableRouteInterface::class,
            new DispatchableRoute(
                route: $this->makeRoute(),
            ),
        );
    }

    public function testAsUrlReturnsUriDirectlyWhenRouteHasNoArguments(): void
    {
        $dispatchable = new DispatchableRoute(
            route: $this->makeRoute(),
        );

        self::assertSame('/home', $dispatchable->asUrl());
    }

    public function testAsUrlInterpolatesRequiredArgument(): void
    {
        $route = $this->makeRoute(
            uri: '/users/{id}',
            arguments: [
                $this->makeArgument('id'),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [
                'id' => '42',
            ],
        );

        self::assertSame('/users/42', $dispatchable->asUrl());
    }

    public function testAsUrlInterpolatesMultipleRequiredArguments(): void
    {
        $route = $this->makeRoute(
            uri: '/users/{id}/posts/{slug}',
            arguments: [
                $this->makeArgument('id'),
                $this->makeArgument('slug'),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [
                'id' => '3',
                'slug' => 'hello-world',
            ],
        );

        self::assertSame('/users/3/posts/hello-world', $dispatchable->asUrl());
    }

    public function testAsUrlReturnsNullWhenRequiredArgumentMissing(): void
    {
        $route = $this->makeRoute(
            uri: '/users/{id}',
            arguments: [
                $this->makeArgument('id'),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [],
        );

        self::assertNull($dispatchable->asUrl());
    }

    public function testAsUrlOmitsOptionalArgumentWhenMissing(): void
    {
        $route = $this->makeRoute(
            uri: '/posts/{?page}',
            arguments: [
                $this->makeArgument(
                    name: 'page',
                    optional: true,
                ),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [],
        );

        self::assertSame('/posts', $dispatchable->asUrl());
    }

    public function testAsUrlIncludesOptionalArgumentWhenProvided(): void
    {
        $route = $this->makeRoute(
            uri: '/posts/{?page}',
            arguments: [
                $this->makeArgument(
                    name: 'page',
                    optional: true,
                ),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [
                'page' => '2',
            ],
        );

        self::assertSame('/posts/2', $dispatchable->asUrl());
    }

    public function testAsUrlResolvesArgumentViaMappedName(): void
    {
        $route = $this->makeRoute(
            uri: '/users/{userId}',
            arguments: [
                $this->makeArgument(
                    name: 'userId',
                    mappedName: 'id',
                ),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [
                'id' => '9',
            ],
        );

        self::assertSame('/users/9', $dispatchable->asUrl());
    }

    public function testAsUrlReturnsNullWhenRequiredMappedArgumentMissing(): void
    {
        $route = $this->makeRoute(
            uri: '/users/{userId}',
            arguments: [
                $this->makeArgument(
                    name: 'userId',
                    mappedName: 'id',
                ),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [],
        );

        self::assertNull($dispatchable->asUrl());
    }

    public function testAsUrlPrefersNodeNameOverMappedName(): void
    {
        $route = $this->makeRoute(
            uri: '/users/{userId}',
            arguments: [
                $this->makeArgument(
                    name: 'userId',
                    mappedName: 'id',
                ),
            ],
        );

        $dispatchable = new DispatchableRoute(
            route: $route,
            arguments: [
                'userId' => '5',
                'id' => '99',
            ],
        );

        self::assertSame('/users/5', $dispatchable->asUrl());
    }
}
