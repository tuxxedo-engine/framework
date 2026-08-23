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

namespace Unit\Router\Builder;

use Fixture\Router\Builder\SimpleController;
use Fixture\Router\Builder\UserController;
use Fixture\Router\RouteDiscoverer\Discovery\DuplicateArgument\DuplicateArgumentController;
use Fixture\Router\RouteDiscoverer\Discovery\Labeled\LabeledController;
use Fixture\Router\RouteDiscoverer\Discovery\MissingParameter\MissingParameterController;
use Fixture\Router\RouteDiscoverer\Discovery\MultiParam\MultiParamController;
use Fixture\Router\RouteDiscoverer\Discovery\NoType\NoTypeController;
use Fixture\Router\RouteDiscoverer\Discovery\NullableClassType\NullableClassTypeController;
use Fixture\Router\RouteDiscoverer\Discovery\Optional\OptionalController;
use Fixture\Router\RouteDiscoverer\Discovery\OptionalNoDefault\OptionalNoDefaultController;
use Fixture\Router\RouteDiscoverer\Discovery\UnsupportedNativeType\UnsupportedNativeTypeController;
use Fixture\Router\RouteDiscoverer\Discovery\UnsupportedType\UnsupportedTypeController;
use Fixture\Router\RouteDiscoverer\Support\AnotherMiddleware;
use Fixture\Router\RouteDiscoverer\Support\TestMiddleware;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Method;
use Tuxxedo\Router\Builder\RouteBuilder;
use Tuxxedo\Router\Builder\RouteBuilderGroup;
use Tuxxedo\Router\Builder\RouteBuilderGroupInterface;
use Tuxxedo\Router\RoutePriority;
use Tuxxedo\Router\RouterException;

class RouteBuilderTest extends TestCase
{
    private function builder(): RouteBuilder
    {
        return new RouteBuilder(
            container: new Container(),
        );
    }

    public function testGetPushesSingleRouteAndBuildMaterializesIt(): void
    {
        $routes = $this->builder()
            ->get(
                uri: '/',
                controller: SimpleController::class,
                action: 'home',
                name: 'home',
            )
            ->build();

        self::assertCount(1, $routes);
        self::assertSame(Method::GET, $routes[0]->method);
        self::assertSame('/', $routes[0]->path);
        self::assertSame(SimpleController::class, $routes[0]->controller);
        self::assertSame('home', $routes[0]->action);
        self::assertSame('home', $routes[0]->name);
        self::assertSame('#^/$#', $routes[0]->regexPath);
        self::assertSame(
            [],
            $routes[0]->arguments,
        );
    }

    public function testEveryVerbEmitsRouteWithCorrespondingMethod(): void
    {
        $routes = $this->builder()
            ->get(uri: '/g', controller: SimpleController::class, action: 'home')
            ->post(uri: '/p', controller: SimpleController::class, action: 'home')
            ->put(uri: '/u', controller: SimpleController::class, action: 'home')
            ->patch(uri: '/a', controller: SimpleController::class, action: 'home')
            ->delete(uri: '/d', controller: SimpleController::class, action: 'home')
            ->options(uri: '/o', controller: SimpleController::class, action: 'home')
            ->head(uri: '/h', controller: SimpleController::class, action: 'home')
            ->connect(uri: '/c', controller: SimpleController::class, action: 'home')
            ->trace(uri: '/t', controller: SimpleController::class, action: 'home')
            ->query(uri: '/q', controller: SimpleController::class, action: 'home')
            ->any(uri: '/x', controller: SimpleController::class, action: 'home')
            ->build();

        self::assertCount(11, $routes);
        self::assertSame(Method::GET, $routes[0]->method);
        self::assertSame(Method::POST, $routes[1]->method);
        self::assertSame(Method::PUT, $routes[2]->method);
        self::assertSame(Method::PATCH, $routes[3]->method);
        self::assertSame(Method::DELETE, $routes[4]->method);
        self::assertSame(Method::OPTIONS, $routes[5]->method);
        self::assertSame(Method::HEAD, $routes[6]->method);
        self::assertSame(Method::CONNECT, $routes[7]->method);
        self::assertSame(Method::TRACE, $routes[8]->method);
        self::assertSame(Method::QUERY, $routes[9]->method);
        self::assertNull($routes[10]->method);
    }

    public function testRouteWithPathArgumentReflectsControllerParameters(): void
    {
        $routes = $this->builder()
            ->get(
                uri: '/users/{id:\d+}',
                controller: UserController::class,
                action: 'show',
            )
            ->build();

        self::assertCount(1, $routes);
        self::assertCount(1, $routes[0]->arguments);
        self::assertSame('id', $routes[0]->arguments[0]->node->name);
        self::assertSame('int', $routes[0]->arguments[0]->nativeType);
        self::assertFalse($routes[0]->arguments[0]->allowsNull);
    }

    public function testPriorityDefaultsToNormalAndCanBeOverridden(): void
    {
        $routes = $this->builder()
            ->get(uri: '/a', controller: SimpleController::class, action: 'home')
            ->get(
                uri: '/b',
                controller: SimpleController::class,
                action: 'home',
                priority: RoutePriority::HOT,
            )
            ->build();

        self::assertSame(RoutePriority::NORMAL, $routes[0]->priority);
        self::assertSame(RoutePriority::HOT, $routes[1]->priority);
    }

    public function testGroupPrefixesChildUrisAndPassesGroupToCallback(): void
    {
        $routes = $this->builder()
            ->group(
                uri: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        uri: '',
                        controller: SimpleController::class,
                        action: 'home',
                    );
                    $group->post(
                        uri: '/users',
                        controller: SimpleController::class,
                        action: 'about',
                    );
                },
            )
            ->build();

        self::assertCount(2, $routes);
        self::assertSame('/admin', $routes[0]->path);
        self::assertSame('/admin/users', $routes[1]->path);
    }

    public function testChildUriWithoutLeadingSlashIsNormalised(): void
    {
        $routes = $this->builder()
            ->group(
                uri: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        uri: 'users',
                        controller: SimpleController::class,
                        action: 'home',
                    );
                },
            )
            ->build();

        self::assertSame('/admin/users', $routes[0]->path);
    }

    public function testGroupWithTrailingSlashPrefixDoesNotDoubleSeparate(): void
    {
        $routes = $this->builder()
            ->group(
                uri: '/admin/',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        uri: '/users',
                        controller: SimpleController::class,
                        action: 'home',
                    );
                },
            )
            ->build();

        self::assertSame('/admin/users', $routes[0]->path);
    }

    public function testNestedGroupsConcatenatePrefixes(): void
    {
        $routes = $this->builder()
            ->group(
                uri: '/api',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $outer): void {
                    $outer->group(
                        uri: '/v1',
                        middleware: [],
                        callback: static function (RouteBuilderGroupInterface $inner): void {
                            $inner->get(
                                uri: '/status',
                                controller: SimpleController::class,
                                action: 'home',
                            );
                        },
                    );
                },
            )
            ->build();

        self::assertSame('/api/v1/status', $routes[0]->path);
    }

    public function testGroupMiddlewareMergesInOutermostFirstOrderWithChildAppended(): void
    {
        $routes = $this->builder()
            ->group(
                uri: '/admin',
                middleware: [
                    TestMiddleware::class,
                ],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        uri: '',
                        controller: SimpleController::class,
                        action: 'home',
                        middleware: [
                            AnotherMiddleware::class,
                        ],
                    );
                },
            )
            ->build();

        self::assertCount(2, $routes[0]->middleware);
    }

    public function testClosureMiddlewarePassesThroughVerbatim(): void
    {
        $captured = null;

        $middlewareFactory = static function () use (&$captured): TestMiddleware {
            $captured = new TestMiddleware();

            return $captured;
        };

        $routes = $this->builder()
            ->get(
                uri: '/x',
                controller: SimpleController::class,
                action: 'home',
                middleware: [
                    $middlewareFactory,
                ],
            )
            ->build();

        self::assertCount(1, $routes[0]->middleware);
        self::assertSame($middlewareFactory, $routes[0]->middleware[0]);
    }

    public function testClassStringMiddlewareIsWrappedInContainerResolvingClosure(): void
    {
        $container = new Container();
        $container->singleton(new TestMiddleware());

        $builder = new RouteBuilder(
            container: $container,
        );

        $routes = $builder->get(
            uri: '/x',
            controller: SimpleController::class,
            action: 'home',
            middleware: [
                TestMiddleware::class,
            ],
        )->build();

        self::assertCount(1, $routes[0]->middleware);
        self::assertInstanceOf(\Closure::class, $routes[0]->middleware[0]);
        self::assertInstanceOf(TestMiddleware::class, $routes[0]->middleware[0]());
    }

    public function testBuildReturnsEmptyListWhenNoRoutesRegistered(): void
    {
        self::assertSame(
            [],
            $this->builder()->build(),
        );
    }

    public function testJoinUriUsesPrefixVerbatimWhenChildIsEmptyString(): void
    {
        self::assertSame(
            '/admin',
            RouteBuilderGroup::joinUri(
                prefix: '/admin',
                child: '',
            ),
        );
    }

    public function testDuplicateArgumentNamesInPathThrows(): void
    {
        $builder = $this->builder()->get(
            uri: '/items/{id}/{id}',
            controller: DuplicateArgumentController::class,
            action: 'show',
        );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('non-unique', $exception->getMessage());
        }
    }

    public function testArgumentWithNoMatchingParameterThrows(): void
    {
        $builder = $this->builder()->get(
            uri: '/users/{id}',
            controller: MissingParameterController::class,
            action: 'show',
        );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('does not match any parameter', $exception->getMessage());
        }
    }

    public function testOptionalArgumentWithoutParameterDefaultThrows(): void
    {
        $builder = $this->builder()->get(
            uri: '/page/{?id}',
            controller: OptionalNoDefaultController::class,
            action: 'show',
        );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('no default value', $exception->getMessage());
        }
    }

    public function testOptionalArgumentWithParameterDefaultResolvesTheDefault(): void
    {
        $routes = $this->builder()->get(
            uri: '/page/{?id}',
            controller: OptionalController::class,
            action: 'show',
        )->build();

        self::assertCount(1, $routes[0]->arguments);
        self::assertTrue($routes[0]->arguments[0]->node->optional);
        self::assertSame(1, $routes[0]->arguments[0]->defaultValue);
    }

    public function testLabeledArgumentMapsPathNameToParameterName(): void
    {
        $routes = $this->builder()->get(
            uri: '/users/{userId}',
            controller: LabeledController::class,
            action: 'show',
        )->build();

        self::assertCount(1, $routes[0]->arguments);
        self::assertSame('userId', $routes[0]->arguments[0]->node->name);
        self::assertSame('id', $routes[0]->arguments[0]->mappedName);
    }

    public function testParameterWithoutTypeThrows(): void
    {
        $builder = $this->builder()->get(
            uri: '/values/{value}',
            controller: NoTypeController::class,
            action: 'show',
        );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('has no type', $exception->getMessage());
        }
    }

    public function testArrayParameterTypeThrowsUnsupportedNative(): void
    {
        $builder = $this->builder()->get(
            uri: '/values/{value}',
            controller: UnsupportedNativeTypeController::class,
            action: 'show',
        );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('not supported', $exception->getMessage());
        }
    }

    public function testNonBuiltinNonNullableParameterThrowsUnsupportedType(): void
    {
        $builder = $this->builder()->get(
            uri: '/items/{value}',
            controller: UnsupportedTypeController::class,
            action: 'show',
        );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('unsupported type', $exception->getMessage());
        }
    }

    public function testLaterParameterIsFoundAfterSkippingUnrelatedEarlierOnes(): void
    {
        $routes = $this->builder()->get(
            uri: '/users/{id}/{page}',
            controller: MultiParamController::class,
            action: 'show',
        )->build();

        self::assertCount(2, $routes[0]->arguments);
        self::assertSame('id', $routes[0]->arguments[0]->node->name);
        self::assertSame('page', $routes[0]->arguments[1]->node->name);
    }

    public function testNullableNonBuiltinParameterResolvesToNullNativeType(): void
    {
        $routes = $this->builder()->get(
            uri: '/items/{value}',
            controller: NullableClassTypeController::class,
            action: 'show',
        )->build();

        self::assertCount(1, $routes[0]->arguments);
        self::assertSame('null', $routes[0]->arguments[0]->nativeType);
    }

    public function testGroupExercisesEveryVerb(): void
    {
        $routes = $this->builder()
            ->group(
                uri: '/api',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(uri: '/g', controller: SimpleController::class, action: 'home');
                    $group->post(uri: '/p', controller: SimpleController::class, action: 'home');
                    $group->put(uri: '/u', controller: SimpleController::class, action: 'home');
                    $group->patch(uri: '/a', controller: SimpleController::class, action: 'home');
                    $group->delete(uri: '/d', controller: SimpleController::class, action: 'home');
                    $group->options(uri: '/o', controller: SimpleController::class, action: 'home');
                    $group->head(uri: '/h', controller: SimpleController::class, action: 'home');
                    $group->connect(uri: '/c', controller: SimpleController::class, action: 'home');
                    $group->trace(uri: '/t', controller: SimpleController::class, action: 'home');
                    $group->query(uri: '/q', controller: SimpleController::class, action: 'home');
                    $group->any(uri: '/x', controller: SimpleController::class, action: 'home');
                },
            )
            ->build();

        self::assertCount(11, $routes);
        self::assertSame(Method::GET, $routes[0]->method);
        self::assertSame(Method::POST, $routes[1]->method);
        self::assertSame(Method::PUT, $routes[2]->method);
        self::assertSame(Method::PATCH, $routes[3]->method);
        self::assertSame(Method::DELETE, $routes[4]->method);
        self::assertSame(Method::OPTIONS, $routes[5]->method);
        self::assertSame(Method::HEAD, $routes[6]->method);
        self::assertSame(Method::CONNECT, $routes[7]->method);
        self::assertSame(Method::TRACE, $routes[8]->method);
        self::assertSame(Method::QUERY, $routes[9]->method);
        self::assertNull($routes[10]->method);

        foreach ($routes as $route) {
            self::assertStringStartsWith('/api/', $route->path);
        }
    }
}
