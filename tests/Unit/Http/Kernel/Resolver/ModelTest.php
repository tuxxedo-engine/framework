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

namespace Unit\Http\Kernel\Resolver;

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Model\StubModelsManager;
use Support\Reflection\StubParameterReflector;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\Resolver\Model;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\ArgumentNode;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\Route;
use Tuxxedo\Router\RouteArgument;

class ModelTest extends TestCase
{
    /**
     * @param array<string, string> $routeArguments
     */
    private function containerWith(
        StubModelsManager $modelsManager,
        array $routeArguments = [],
    ): ContainerInterface {
        $container = new Container();

        $container->singleton(
            class: $modelsManager,
        );

        $container->singleton(
            class: $this->makeRequest(
                routeArguments: $routeArguments,
            ),
        );

        return $container;
    }

    /**
     * @param array<string, string> $routeArguments
     */
    private function makeRequest(
        array $routeArguments = [],
    ): RequestInterface {
        $request = new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        );

        return $request->withRoute(
            route: new DispatchableRoute(
                route: new Route(
                    method: null,
                    path: '/users/{id}',
                    controller: \stdClass::class,
                    action: 'stub',
                    arguments: [
                        new RouteArgument(
                            node: new ArgumentNode(
                                name: 'id',
                                kind: ArgumentKind::TYPED_EXPLICIT,
                            ),
                            mappedName: null,
                            nativeType: 'string',
                            allowsNull: false,
                            defaultValue: null,
                            resolverConsumed: true,
                        ),
                    ],
                ),
                arguments: $routeArguments,
            ),
        );
    }

    public function testRouteArgumentsHookReportsConstructorArgumentName(): void
    {
        $resolver = new Model(
            argumentName: 'id',
        );

        self::assertSame(
            [
                'id',
            ],
            $resolver->routeArguments,
        );
    }

    public function testResolveReturnsModelFromManagerOnHappyPath(): void
    {
        $user = new \stdClass();
        $modelsManager = new StubModelsManager(
            findByIdReturn: $user,
        );

        $resolver = new Model(
            argumentName: 'id',
        );

        $resolved = $resolver->resolve(
            container: $this->containerWith(
                modelsManager: $modelsManager,
                routeArguments: [
                    'id' => '42',
                ],
            ),
            parameter: new StubParameterReflector(
                defaultType: \stdClass::class,
            ),
        );

        self::assertSame($user, $resolved);
        self::assertSame(
            [
                [
                    'class' => \stdClass::class,
                    'id' => '42',
                ],
            ],
            $modelsManager->findByIdCalls,
        );
    }

    public function testResolveThrowsWhenParameterTypeIsMissingAndParameterIsNotNullable(): void
    {
        $resolver = new Model(
            argumentName: 'id',
        );

        try {
            $resolver->resolve(
                container: $this->containerWith(
                    modelsManager: new StubModelsManager(),
                ),
                parameter: new StubParameterReflector(),
            );

            self::fail('Expected ModelException');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'Invalid model class supplied to the #[Model] attribute',
                $exception->getMessage(),
            );
        }
    }

    public function testResolveReturnsNullWhenParameterTypeIsMissingAndParameterIsNullable(): void
    {
        $resolver = new Model(
            argumentName: 'id',
        );

        $resolved = $resolver->resolve(
            container: $this->containerWith(
                modelsManager: new StubModelsManager(),
            ),
            parameter: new StubParameterReflector(
                nullable: true,
            ),
        );

        self::assertNull($resolved);
    }

    public function testResolveThrowsNotFoundWhenRouteArgumentIsMissingAndParameterIsNotNullable(): void
    {
        $resolver = new Model(
            argumentName: 'id',
        );

        $this->expectException(HttpException::class);

        $resolver->resolve(
            container: $this->containerWith(
                modelsManager: new StubModelsManager(),
            ),
            parameter: new StubParameterReflector(
                defaultType: \stdClass::class,
            ),
        );
    }

    public function testResolveReturnsNullWhenRouteArgumentIsMissingAndParameterIsNullable(): void
    {
        $resolver = new Model(
            argumentName: 'id',
        );

        $resolved = $resolver->resolve(
            container: $this->containerWith(
                modelsManager: new StubModelsManager(),
            ),
            parameter: new StubParameterReflector(
                defaultType: \stdClass::class,
                nullable: true,
            ),
        );

        self::assertNull($resolved);
    }

    public function testResolveThrowsNotFoundWhenRowIsMissingAndParameterIsNotNullable(): void
    {
        $modelsManager = new StubModelsManager(
            findByIdReturn: null,
        );

        $resolver = new Model(
            argumentName: 'id',
        );

        $this->expectException(HttpException::class);

        $resolver->resolve(
            container: $this->containerWith(
                modelsManager: $modelsManager,
                routeArguments: [
                    'id' => '42',
                ],
            ),
            parameter: new StubParameterReflector(
                defaultType: \stdClass::class,
            ),
        );
    }

    public function testResolveReturnsNullWhenRowIsMissingAndParameterIsNullable(): void
    {
        $modelsManager = new StubModelsManager(
            findByIdReturn: null,
        );

        $resolver = new Model(
            argumentName: 'id',
        );

        $resolved = $resolver->resolve(
            container: $this->containerWith(
                modelsManager: $modelsManager,
                routeArguments: [
                    'id' => '42',
                ],
            ),
            parameter: new StubParameterReflector(
                defaultType: \stdClass::class,
                nullable: true,
            ),
        );

        self::assertNull($resolved);
    }
}
