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
use Fixture\Router\RouteDiscoverer\Discovery\ConsumerDuplicate\ConsumerDuplicateController;
use Fixture\Router\RouteDiscoverer\Discovery\DuplicateArgument\DuplicateArgumentController;
use Fixture\Router\RouteDiscoverer\Discovery\EnumArgument\EnumArgumentController;
use Fixture\Router\RouteDiscoverer\Discovery\Labeled\LabeledController;
use Fixture\Router\RouteDiscoverer\Discovery\Middleware\MiddlewareController;
use Fixture\Router\RouteDiscoverer\Discovery\MissingParameter\MissingParameterController;
use Fixture\Router\RouteDiscoverer\Discovery\MultiParam\MultiParamController;
use Fixture\Router\RouteDiscoverer\Discovery\NoType\NoTypeController;
use Fixture\Router\RouteDiscoverer\Discovery\NullableClassType\NullableClassTypeController;
use Fixture\Router\RouteDiscoverer\Discovery\Optional\OptionalController;
use Fixture\Router\RouteDiscoverer\Discovery\OptionalNoDefault\OptionalNoDefaultController;
use Fixture\Router\RouteDiscoverer\Discovery\StickyPrefix\StickyPrefixController;
use Fixture\Router\RouteDiscoverer\Discovery\TrailingSlash\TrailingController;
use Fixture\Router\RouteDiscoverer\Discovery\UnionType\UnionTypeController;
use Fixture\Router\RouteDiscoverer\Discovery\UnsupportedNativeType\UnsupportedNativeTypeController;
use Fixture\Router\RouteDiscoverer\Discovery\UnsupportedType\UnsupportedTypeController;
use Fixture\Router\RouteDiscoverer\Support\AnotherMiddleware;
use Fixture\Router\RouteDiscoverer\Support\TestMiddleware;
use Fixture\Router\RouteDiscoverer\Support\TestPrefixWithDefaults;
use Fixture\Router\RouteDiscoverer\Support\TestStickyPrefix;
use Fixture\Router\RouteDiscoverer\Support\TestStringEnum;
use Fixture\Router\RouteDiscoverer\Support\TestUnitEnum;
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
                path: '/',
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
        self::assertNull($routes[0]->regexPath);
        self::assertSame(
            [],
            $routes[0]->arguments,
        );
    }

    public function testEveryVerbEmitsRouteWithCorrespondingMethod(): void
    {
        $routes = $this->builder()
            ->get(path: '/g', controller: SimpleController::class, action: 'home')
            ->post(path: '/p', controller: SimpleController::class, action: 'home')
            ->put(path: '/u', controller: SimpleController::class, action: 'home')
            ->patch(path: '/a', controller: SimpleController::class, action: 'home')
            ->delete(path: '/d', controller: SimpleController::class, action: 'home')
            ->options(path: '/o', controller: SimpleController::class, action: 'home')
            ->head(path: '/h', controller: SimpleController::class, action: 'home')
            ->connect(path: '/c', controller: SimpleController::class, action: 'home')
            ->trace(path: '/t', controller: SimpleController::class, action: 'home')
            ->query(path: '/q', controller: SimpleController::class, action: 'home')
            ->any(path: '/x', controller: SimpleController::class, action: 'home')
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
                path: '/users/{id:\d+}',
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
            ->get(path: '/a', controller: SimpleController::class, action: 'home')
            ->get(
                path: '/b',
                controller: SimpleController::class,
                action: 'home',
                priority: RoutePriority::HOT,
            )
            ->build();

        self::assertSame(RoutePriority::NORMAL, $routes[0]->priority);
        self::assertSame(RoutePriority::HOT, $routes[1]->priority);
    }

    public function testGroupPrefixesChildPathsAndPassesGroupToCallback(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: '',
                        controller: SimpleController::class,
                        action: 'home',
                    );
                    $group->post(
                        path: '/users',
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

    public function testChildPathWithoutLeadingSlashIsNormalised(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: 'users',
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
                path: '/admin/',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: '/users',
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
                path: '/api',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $outer): void {
                    $outer->group(
                        path: '/v1',
                        middleware: [],
                        callback: static function (RouteBuilderGroupInterface $inner): void {
                            $inner->get(
                                path: '/status',
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
                path: '/admin',
                middleware: [
                    TestMiddleware::class,
                ],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: '',
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
                path: '/x',
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
            path: '/x',
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

    public function testJoinPathUsesPrefixVerbatimWhenChildIsEmptyString(): void
    {
        self::assertSame(
            '/admin',
            RouteBuilderGroup::joinPath(
                prefix: '/admin',
                child: '',
            ),
        );
    }

    public function testGroupNamePrefixIsPrependedToNamedChildRoutes(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: '/dashboard',
                        controller: SimpleController::class,
                        action: 'home',
                        name: 'dashboard',
                    );
                    $group->post(
                        path: '/users',
                        controller: SimpleController::class,
                        action: 'about',
                        name: 'users.create',
                    );
                },
                name: 'admin.',
            )
            ->build();

        self::assertSame('admin.dashboard', $routes[0]->name);
        self::assertSame('admin.users.create', $routes[1]->name);
    }

    public function testGroupNamePrefixLeavesUnnamedChildRoutesUnchanged(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: '/dashboard',
                        controller: SimpleController::class,
                        action: 'home',
                    );
                },
                name: 'admin.',
            )
            ->build();

        self::assertNull($routes[0]->name);
    }

    public function testGroupWithoutNamePrefixLeavesChildNamesUnchanged(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: '/dashboard',
                        controller: SimpleController::class,
                        action: 'home',
                        name: 'dashboard',
                    );
                },
            )
            ->build();

        self::assertSame('dashboard', $routes[0]->name);
    }

    public function testNestedGroupsConcatenateNamePrefixes(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/api',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $outer): void {
                    $outer->group(
                        path: '/v1',
                        middleware: [],
                        callback: static function (RouteBuilderGroupInterface $inner): void {
                            $inner->get(
                                path: '/status',
                                controller: SimpleController::class,
                                action: 'home',
                                name: 'status',
                            );
                        },
                        name: 'v1.',
                    );
                },
                name: 'api.',
            )
            ->build();

        self::assertSame('api.v1.status', $routes[0]->name);
    }

    public function testDuplicateRouteNameThrows(): void
    {
        $builder = $this->builder()
            ->get(
                path: '/a',
                controller: SimpleController::class,
                action: 'home',
                name: 'shared',
            )
            ->get(
                path: '/b',
                controller: SimpleController::class,
                action: 'about',
                name: 'shared',
            );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('shared', $exception->getMessage());
            self::assertStringContainsString('non-unique', $exception->getMessage());
        }
    }

    public function testUnnamedRoutesDoNotCollide(): void
    {
        $routes = $this->builder()
            ->get(
                path: '/a',
                controller: SimpleController::class,
                action: 'home',
            )
            ->get(
                path: '/b',
                controller: SimpleController::class,
                action: 'about',
            )
            ->build();

        self::assertCount(2, $routes);
        self::assertNull($routes[0]->name);
        self::assertNull($routes[1]->name);
    }

    public function testDuplicateRouteNameAcrossGroupsThrows(): void
    {
        $builder = $this->builder()
            ->get(
                path: '/a',
                controller: SimpleController::class,
                action: 'home',
                name: 'dashboard',
            )
            ->group(
                path: '/admin',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(
                        path: '/panel',
                        controller: SimpleController::class,
                        action: 'about',
                        name: 'dashboard',
                    );
                },
            );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('non-unique', $exception->getMessage());
        }
    }

    public function testNestedGroupInheritsOuterNamePrefixWhenInnerHasNone(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/api',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $outer): void {
                    $outer->group(
                        path: '/v1',
                        middleware: [],
                        callback: static function (RouteBuilderGroupInterface $inner): void {
                            $inner->get(
                                path: '/status',
                                controller: SimpleController::class,
                                action: 'home',
                                name: 'status',
                            );
                        },
                    );
                },
                name: 'api.',
            )
            ->build();

        self::assertSame('api.status', $routes[0]->name);
    }

    public function testDuplicateArgumentNamesInPathThrows(): void
    {
        $builder = $this->builder()->get(
            path: '/items/{id}/{id}',
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
            path: '/users/{id}',
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
            path: '/page/{?id}',
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
            path: '/page/{?id}',
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
            path: '/users/{userId}',
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
            path: '/values/{value}',
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
            path: '/values/{value}',
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
            path: '/items/{value}',
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

    public function testUnionParameterThrowsUnsupportedType(): void
    {
        $builder = $this->builder()->get(
            path: '/items/{value}',
            controller: UnionTypeController::class,
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
            path: '/users/{id}/{page}',
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
            path: '/items/{value}',
            controller: NullableClassTypeController::class,
            action: 'show',
        )->build();

        self::assertCount(1, $routes[0]->arguments);
        self::assertSame('null', $routes[0]->arguments[0]->nativeType);
    }

    public function testEnumParameterRecordsEnumClassAndConstrainsRegex(): void
    {
        $routes = $this->builder()->get(
            path: '/feeds/{feed}',
            controller: EnumArgumentController::class,
            action: 'feed',
        )->build();

        self::assertCount(1, $routes[0]->arguments);
        self::assertSame(TestStringEnum::class, $routes[0]->arguments[0]->nativeType);
        self::assertSame(TestStringEnum::class, $routes[0]->arguments[0]->enumClass);
        self::assertSame('#^/feeds/(?<feed>newest|best\.sellers)$#', $routes[0]->regexPath);
    }

    public function testOptionalNullableEnumParameterAllowsNullAndConstrainsRegex(): void
    {
        $routes = $this->builder()->get(
            path: '/units/{?unit}',
            controller: EnumArgumentController::class,
            action: 'unit',
        )->build();

        self::assertSame(TestUnitEnum::class, $routes[0]->arguments[0]->enumClass);
        self::assertTrue($routes[0]->arguments[0]->allowsNull);
        self::assertSame('#^/units(?:/(?<unit>ALPHA|BETA))?$#', $routes[0]->regexPath);
    }

    public function testBuiltinParameterLeavesEnumClassNull(): void
    {
        $routes = $this->builder()->get(
            path: '/users/{id}',
            controller: UserController::class,
            action: 'show',
        )->build();

        self::assertNull($routes[0]->arguments[0]->enumClass);
        self::assertSame('#^/users/(?<id>[^/]+)$#', $routes[0]->regexPath);
    }

    public function testGroupExercisesEveryVerb(): void
    {
        $routes = $this->builder()
            ->group(
                path: '/api',
                middleware: [],
                callback: static function (RouteBuilderGroupInterface $group): void {
                    $group->get(path: '/g', controller: SimpleController::class, action: 'home');
                    $group->post(path: '/p', controller: SimpleController::class, action: 'home');
                    $group->put(path: '/u', controller: SimpleController::class, action: 'home');
                    $group->patch(path: '/a', controller: SimpleController::class, action: 'home');
                    $group->delete(path: '/d', controller: SimpleController::class, action: 'home');
                    $group->options(path: '/o', controller: SimpleController::class, action: 'home');
                    $group->head(path: '/h', controller: SimpleController::class, action: 'home');
                    $group->connect(path: '/c', controller: SimpleController::class, action: 'home');
                    $group->trace(path: '/t', controller: SimpleController::class, action: 'home');
                    $group->query(path: '/q', controller: SimpleController::class, action: 'home');
                    $group->any(path: '/x', controller: SimpleController::class, action: 'home');
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

    public function testArgumentClaimedTwiceThrows(): void
    {
        $builder = $this->builder()->get(
            path: '/users/{id}',
            controller: ConsumerDuplicateController::class,
            action: 'show',
        );

        try {
            $builder->build();

            self::fail('Expected RouterException was not thrown');
        } catch (RouterException $exception) {
            self::assertStringContainsString('claimed by more than one', $exception->getMessage());
        }
    }

    public function testGroupPrefixAppliesAndInnermostPrefixWins(): void
    {
        $routes = $this->builder()->group(
            path: '/a',
            middleware: [],
            callback: static function (RouteBuilderGroupInterface $group): void {
                $group->group(
                    path: '',
                    middleware: [],
                    callback: static function (RouteBuilderGroupInterface $group): void {
                        $group->get(
                            path: '/home',
                            controller: StickyPrefixController::class,
                            action: 'home',
                        );
                    },
                    prefix: TestStickyPrefix::class,
                );
            },
            prefix: TestPrefixWithDefaults::class,
        )->build();

        self::assertCount(1, $routes);
        self::assertSame('/{tenant:[a-z]+}/a/home', $routes[0]->path);
        self::assertSame(TestStickyPrefix::class, $routes[0]->stickyPrefix);
        self::assertSame('tenant', $routes[0]->arguments[0]->node->name);
        self::assertTrue($routes[0]->arguments[0]->node->prefixed);
    }

    public function testRoutePrefixOverridesGroupPrefix(): void
    {
        $routes = $this->builder()->group(
            path: '',
            middleware: [],
            callback: static function (RouteBuilderGroupInterface $group): void {
                $group->get(
                    path: '/home',
                    controller: StickyPrefixController::class,
                    action: 'home',
                    prefix: TestStickyPrefix::class,
                );
            },
            prefix: '/api',
        )->build();

        self::assertSame('/{tenant:[a-z]+}/home', $routes[0]->path);
        self::assertSame(TestStickyPrefix::class, $routes[0]->stickyPrefix);
    }

    public function testGroupTrailingSlashAddsAnUnnamedSlashVariant(): void
    {
        $routes = $this->builder()->group(
            path: '/x',
            middleware: [],
            callback: static function (RouteBuilderGroupInterface $group): void {
                $group->get(
                    path: '/page',
                    controller: TrailingController::class,
                    action: 'page',
                    name: 'page',
                );
            },
            trailingSlash: true,
        )->build();

        self::assertCount(2, $routes);
        self::assertSame('/x/page', $routes[0]->path);
        self::assertSame('page', $routes[0]->name);
        self::assertSame('/x/page/', $routes[1]->path);
        self::assertNull($routes[1]->name);
    }

    public function testExplicitMiddlewareRunsBeforeAttributeMiddleware(): void
    {
        $routes = $this->builder()->get(
            path: '/protected',
            controller: MiddlewareController::class,
            action: 'protected',
            middleware: [
                AnotherMiddleware::class,
            ],
        )->build();

        self::assertSame(
            [
                AnotherMiddleware::class,
                TestMiddleware::class,
                AnotherMiddleware::class,
            ],
            \array_map(
                static fn (\Closure $middleware): string => $middleware()::class,
                $routes[0]->middleware,
            ),
        );
    }

    public function testGroupMatchEmitsOneRoutePerDistinctMethod(): void
    {
        $routes = $this->builder()->group(
            path: '/forms',
            middleware: [],
            callback: static function (RouteBuilderGroupInterface $group): void {
                $group->match(
                    methods: [
                        Method::GET,
                        Method::POST,
                        Method::GET,
                    ],
                    path: '/contact',
                    controller: SimpleController::class,
                    action: 'home',
                );
            },
        )->build();

        self::assertCount(2, $routes);
        self::assertSame(Method::GET, $routes[0]->method);
        self::assertSame(Method::POST, $routes[1]->method);
        self::assertSame('/forms/contact', $routes[0]->path);
        self::assertSame('/forms/contact', $routes[1]->path);
    }
}
