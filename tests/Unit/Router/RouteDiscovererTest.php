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

use Fixture\Router\RouteDiscoverer\Support\AnotherMiddleware;
use Fixture\Router\RouteDiscoverer\Support\TestMiddleware;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Method;
use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\Pattern\TypePatternRegistry;
use Tuxxedo\Router\RouteDiscoverer;
use Tuxxedo\Router\RouteInterface;
use Tuxxedo\Router\RoutePriority;
use Tuxxedo\Router\RouterException;

class RouteDiscovererTest extends TestCase
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

    /**
     * @return RouteInterface[]
     */
    private function discoverAll(
        RouteDiscoverer $discoverer,
        bool $rediscover = false,
    ): array {
        return \iterator_to_array(
            $discoverer->discover(
                rediscover: $rediscover,
            ),
            preserve_keys: false,
        );
    }

    public function testConstructorExposesProvidedDependencies(): void
    {
        $discoverer = new RouteDiscoverer(
            container: $this->container,
            baseNamespace: 'App\\',
            directory: $this->fixtureDir('Simple'),
            strictMode: true,
        );

        self::assertSame('App\\', $discoverer->baseNamespace);
        self::assertSame($this->fixtureDir('Simple'), $discoverer->directory);
        self::assertTrue($discoverer->strictMode);
    }

    public function testConstructorDefaultsStrictModeToFalse(): void
    {
        $discoverer = new RouteDiscoverer(
            container: $this->container,
            baseNamespace: 'App\\',
            directory: $this->fixtureDir('Simple'),
        );

        self::assertFalse($discoverer->strictMode);
    }

    public function testConstructorCreatesDefaultPatternRegistryWhenNoneProvided(): void
    {
        $discoverer = new RouteDiscoverer(
            container: $this->container,
            baseNamespace: 'App\\',
            directory: $this->fixtureDir('Simple'),
        );

        self::assertInstanceOf(TypePatternRegistry::class, $discoverer->patterns);
    }

    public function testConstructorAcceptsCustomPatternRegistry(): void
    {
        $patterns = TypePatternRegistry::createDefault();

        $discoverer = new RouteDiscoverer(
            container: $this->container,
            baseNamespace: 'App\\',
            directory: $this->fixtureDir('Simple'),
            patterns: $patterns,
        );

        self::assertSame($patterns, $discoverer->patterns);
    }

    public function testEmptyDirectoryYieldsNoRoutes(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Empty'),
        );

        self::assertSame([], $routes);
    }

    public function testDiscoversSimpleRoute(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Simple'),
        );

        self::assertCount(1, $routes);
        self::assertSame(Method::GET, $routes[0]->method);
        self::assertSame('/home', $routes[0]->uri);
        self::assertSame(self::FIXTURE_NAMESPACE_BASE . 'Simple\\SimpleController', $routes[0]->controller);
        self::assertSame('home', $routes[0]->action);
        self::assertNull($routes[0]->name);
        self::assertSame(RoutePriority::NORMAL, $routes[0]->priority);
        self::assertSame([], $routes[0]->arguments);
        self::assertNull($routes[0]->regexUri);
    }

    public function testDiscoversMultipleControllers(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Multiple'),
        );

        $uris = \array_map(
            static fn (RouteInterface $route): string => $route->uri,
            $routes,
        );

        \sort($uris);

        self::assertSame(
            [
                '/alpha',
                '/beta',
            ],
            $uris,
        );
    }

    public function testDiscoveryIsCachedAcrossSubsequentCallsWithoutRediscoverFlag(): void
    {
        $discoverer = $this->createDiscoverer('Simple');

        $first = $this->discoverAll($discoverer);
        $second = $this->discoverAll($discoverer);

        self::assertCount(1, $first);
        self::assertSame([], $second);
    }

    public function testRediscoverFlagForcesAdditionalDiscovery(): void
    {
        $discoverer = $this->createDiscoverer('Simple');

        $this->discoverAll($discoverer);

        $second = $this->discoverAll(
            $discoverer,
            rediscover: true,
        );

        self::assertCount(1, $second);
    }

    public function testControllerUriIsPrependedWhenRouteUriIsProvided(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('ControllerPrefix'),
        );

        self::assertCount(1, $routes);
        self::assertSame('/admin/users', $routes[0]->uri);
    }

    public function testAutoIndexUsesControllerUriDirectlyForIndexMethod(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('ControllerOnlyUri'),
        );

        $uris = \array_map(
            static fn (RouteInterface $route): string => $route->uri,
            $routes,
        );

        \sort($uris);

        self::assertSame(
            [
                '/users',
                '/usersdetails',
            ],
            $uris,
        );
    }

    public function testHttpMethodAttributesEmitTheirSpecificMethod(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('HttpMethods'),
        );

        $byUri = [];

        foreach ($routes as $route) {
            $byUri[$route->uri] = $route->method;
        }

        self::assertSame(Method::GET, $byUri['/get']);
        self::assertSame(Method::POST, $byUri['/post']);
        self::assertSame(Method::PUT, $byUri['/put']);
        self::assertSame(Method::PATCH, $byUri['/patch']);
        self::assertSame(Method::DELETE, $byUri['/delete']);
    }

    public function testMultipleMethodsOnSingleRouteAttributeEmitOneRoutePerMethod(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('MultiMethod'),
        );

        $methods = \array_map(
            static fn (RouteInterface $route): ?Method => $route->method,
            $routes,
        );

        self::assertCount(2, $routes);
        self::assertContains(Method::GET, $methods);
        self::assertContains(Method::POST, $methods);
        self::assertSame('/contact', $routes[0]->uri);
        self::assertSame('/contact', $routes[1]->uri);
    }

    public function testRouteAttributeWithoutMethodEmitsRouteWithNullMethod(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('NoMethod'),
        );

        self::assertCount(1, $routes);
        self::assertNull($routes[0]->method);
        self::assertSame('/any', $routes[0]->uri);
    }

    public function testRepeatedRouteAttributesEmitOneRoutePerOccurrence(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Repeated'),
        );

        $uris = \array_map(
            static fn (RouteInterface $route): string => $route->uri,
            $routes,
        );

        \sort($uris);

        self::assertSame(
            [
                '/new',
                '/old',
            ],
            $uris,
        );
    }

    public function testTrailingSlashEmitsBothVariants(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('TrailingSlash'),
        );

        $uris = \array_map(
            static fn (RouteInterface $route): string => $route->uri,
            $routes,
        );

        \sort($uris);

        self::assertSame(
            [
                '/page',
                '/page/',
            ],
            $uris,
        );
    }

    public function testAutoTrailingSlashFromControllerEmitsBothVariants(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('AutoTrailing'),
        );

        $uris = \array_map(
            static fn (RouteInterface $route): string => $route->uri,
            $routes,
        );

        \sort($uris);

        self::assertSame(
            [
                '/articles/list',
                '/articles/list/',
            ],
            $uris,
        );
    }

    public function testIndexAttributeAppliesNameWhenRouteHasNoName(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('IndexNamed'),
        );

        self::assertCount(1, $routes);
        self::assertSame('dashboard.home', $routes[0]->name);
    }

    public function testIndexAttributeEmitsAdditionalAliasWhenRouteAlreadyNamed(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('IndexNamedKeepsExplicit'),
        );

        $names = \array_map(
            static fn (RouteInterface $route): ?string => $route->name,
            $routes,
        );

        \sort($names);

        self::assertSame(
            [
                'dashboard.alias',
                'dashboard.explicit',
            ],
            $names,
        );
    }

    public function testEmptyUriWithoutControllerThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'EmptyUri',
                strictMode: true,
            ),
        );
    }

    public function testEmptyUriWithoutControllerEndsDiscoveryInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('EmptyUri'),
        );

        self::assertSame([], $routes);
    }

    public function testDuplicateRouteNameThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'Duplicate',
                strictMode: true,
            ),
        );
    }

    public function testDuplicateRouteNameSkipsSecondOccurrenceInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Duplicate'),
        );

        self::assertCount(1, $routes);
        self::assertSame('shared', $routes[0]->name);
        self::assertSame('/first', $routes[0]->uri);
    }

    public function testTypedImplicitArgumentEmitsArgumentNodeWithRegexFromRegistry(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('TypedImplicit'),
        );

        self::assertCount(1, $routes);
        self::assertCount(1, $routes[0]->arguments);

        $argument = $routes[0]->arguments[0];

        self::assertSame('id', $argument->node->name);
        self::assertSame(ArgumentKind::TYPED_IMPLICIT, $argument->node->kind);
        self::assertNull($argument->node->constraint);
        self::assertFalse($argument->node->optional);
        self::assertSame('int', $argument->nativeType);
        self::assertNotNull($routes[0]->regexUri);
        self::assertStringContainsString('(?<id>', $routes[0]->regexUri);
    }

    public function testTypedExplicitArgumentRecordsConstraintFromAngleBrackets(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('TypedExplicit'),
        );

        self::assertCount(1, $routes);

        $argument = $routes[0]->arguments[0];

        self::assertSame('slug', $argument->node->name);
        self::assertSame(ArgumentKind::TYPED_EXPLICIT, $argument->node->kind);
        self::assertSame('slug', $argument->node->constraint);
        self::assertSame('string', $argument->nativeType);
    }

    public function testRegexArgumentRecordsRegexConstraint(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Regex'),
        );

        self::assertCount(1, $routes);

        $argument = $routes[0]->arguments[0];

        self::assertSame(ArgumentKind::REGEX, $argument->node->kind);
        self::assertSame('[a-z]+', $argument->node->constraint);
        self::assertIsString($routes[0]->regexUri);
        self::assertStringContainsString('(?<name>[a-z]+)', $routes[0]->regexUri);
    }

    public function testOptionalArgumentWithDefaultEmitsOptionalRegex(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Optional'),
        );

        self::assertCount(1, $routes);

        $argument = $routes[0]->arguments[0];

        self::assertTrue($argument->node->optional);
        self::assertSame(1, $argument->defaultValue);
        self::assertIsString($routes[0]->regexUri);
        self::assertStringContainsString('(?:/(?<id>', $routes[0]->regexUri);
        self::assertStringContainsString(')?', $routes[0]->regexUri);
    }

    public function testLabeledArgumentMapsParameterName(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Labeled'),
        );

        self::assertCount(1, $routes);

        $argument = $routes[0]->arguments[0];

        self::assertSame('userId', $argument->node->name);
        self::assertSame('id', $argument->mappedName);
        self::assertSame('int', $argument->nativeType);
    }

    public function testOptionalArgumentWithoutDefaultThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'OptionalNoDefault',
                strictMode: true,
            ),
        );
    }

    public function testOptionalArgumentWithoutDefaultSkipsInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('OptionalNoDefault'),
        );

        self::assertSame([], $routes);
    }

    public function testUnsupportedNativeTypeThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'UnsupportedNativeType',
                strictMode: true,
            ),
        );
    }

    public function testUnsupportedNativeTypeSkipsInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('UnsupportedNativeType'),
        );

        self::assertSame([], $routes);
    }

    public function testNoTypeThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'NoType',
                strictMode: true,
            ),
        );
    }

    public function testNoTypeSkipsInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('NoType'),
        );

        self::assertSame([], $routes);
    }

    public function testDuplicateArgumentNameThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'DuplicateArgument',
                strictMode: true,
            ),
        );
    }

    public function testPrefixWithDefaultsProvidesDefaultArgumentValue(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('PrefixDefault'),
        );

        self::assertCount(1, $routes);
        self::assertSame('/{locale:[a-z]{2}}/home', $routes[0]->uri);
        self::assertCount(1, $routes[0]->arguments);

        $argument = $routes[0]->arguments[0];

        self::assertSame('locale', $argument->node->name);
        self::assertTrue($argument->node->prefixed);
        self::assertSame('en', $argument->defaultValue);
    }

    public function testRoutePrefixOverridesUri(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('RoutePrefix'),
        );

        self::assertCount(1, $routes);
        self::assertSame('/api/users', $routes[0]->uri);
    }

    public function testAbstractClassThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'AbstractStructure',
                strictMode: true,
            ),
        );
    }

    public function testAbstractClassSkipsInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('AbstractStructure'),
        );

        self::assertSame([], $routes);
    }

    public function testInterfaceThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'InterfaceStructure',
                strictMode: true,
            ),
        );
    }

    public function testInterfaceSkipsInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('InterfaceStructure'),
        );

        self::assertSame([], $routes);
    }

    public function testTraitThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'TraitStructure',
                strictMode: true,
            ),
        );
    }

    public function testTraitSkipsInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('TraitStructure'),
        );

        self::assertSame([], $routes);
    }

    public function testEnumThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'EnumStructure',
                strictMode: true,
            ),
        );
    }

    public function testEnumSkipsInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('EnumStructure'),
        );

        self::assertSame([], $routes);
    }

    public function testStaticMethodThrowsInStrictMode(): void
    {
        self::expectException(RouterException::class);

        $this->discoverAll(
            $this->createDiscoverer(
                scenario: 'StaticMethod',
                strictMode: true,
            ),
        );
    }

    public function testStaticMethodIsSkippedButInstanceMethodIsKeptInNonStrictMode(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('StaticMethod'),
        );

        $uris = \array_map(
            static fn (RouteInterface $route): string => $route->uri,
            $routes,
        );

        self::assertSame(
            [
                '/instance',
            ],
            $uris,
        );
    }

    public function testMiddlewareIsAppendedFromControllerThenMethod(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('Middleware'),
        );

        $byUri = [];

        foreach ($routes as $route) {
            $byUri[$route->uri] = $route;
        }

        self::assertCount(2, $byUri['/protected']->middleware);
        self::assertInstanceOf(TestMiddleware::class, ($byUri['/protected']->middleware[0])());
        self::assertInstanceOf(AnotherMiddleware::class, ($byUri['/protected']->middleware[1])());

        self::assertCount(1, $byUri['/open']->middleware);
        self::assertInstanceOf(TestMiddleware::class, ($byUri['/open']->middleware[0])());
    }

    public function testClosureMiddlewareIsResolvedViaContainerInvocation(): void
    {
        $routes = $this->discoverAll(
            $this->createDiscoverer('ClosureMiddleware'),
        );

        self::assertCount(1, $routes);
        self::assertCount(1, $routes[0]->middleware);
        self::assertInstanceOf(TestMiddleware::class, ($routes[0]->middleware[0])());
    }
}
