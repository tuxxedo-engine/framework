<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit\Container;

use Fixtures\Container\AbstractService;
use Fixtures\Container\ComplexService;
use Fixtures\Container\LazyService;
use Fixtures\Container\PersistentService;
use Fixtures\Container\RebindA;
use Fixtures\Container\RebindB;
use Fixtures\Container\RebindC;
use Fixtures\Container\ServiceInterface;
use Fixtures\Container\ServiceOne;
use Fixtures\Container\ServiceOneInterface;
use Fixtures\Container\ServiceTwo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\AlwaysPersistentInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\LazyInitializableInterface;

class ContainerTest extends TestCase
{
    public function testBasicResolve(): void
    {
        $container = new Container();
        $service = $container->resolve(ServiceOne::class);

        self::assertInstanceOf(ServiceOne::class, $service);
        self::assertSame($service->foo(), 'bar');
    }

    public function testBasicResolveAlias(): void
    {
        $container = new Container();

        $container->alias(ServiceOneInterface::class, ServiceOne::class);

        $service = $container->resolve(ServiceOneInterface::class);

        self::assertInstanceOf(ServiceOne::class, $service);
        self::assertTrue($container->isAlias(ServiceOneInterface::class));
        self::assertTrue($container->isAliasOf(ServiceOneInterface::class, ServiceOne::class));
        self::assertFalse($container->isAliasOf(ServiceOne::class, ServiceOneInterface::class));
    }

    public function testPersistentServiceByResolve(): void
    {
        $container = new Container();

        self::assertFalse($container->isBound(PersistentService::class));

        $service1 = $container->resolve(PersistentService::class);

        self::assertInstanceOf(PersistentService::class, $service1);
        self::assertInstanceOf(AlwaysPersistentInterface::class, $service1);
        self::assertTrue($container->isBound(PersistentService::class));

        $service2 = $container->resolve(PersistentService::class);

        self::assertSame($service1, $service2);
        self::assertSame(\spl_object_id($service1), \spl_object_id($service2));
    }

    public static function bindDataProvider(): \Generator
    {
        yield [
            ComplexService::class,
            ServiceInterface::class,
            AbstractService::class,
            false,
            false,
        ];

        yield [
            ComplexService::class,
            ServiceInterface::class,
            AbstractService::class,
            true,
            false,
        ];

        yield [
            ComplexService::class,
            ServiceInterface::class,
            AbstractService::class,
            false,
            true,
        ];

        yield [
            ComplexService::class,
            ServiceInterface::class,
            AbstractService::class,
            true,
            true,
        ];

        yield [
            ComplexService::class,
            ServiceInterface::class,
            AbstractService::class,
            false,
            false,
        ];
    }

    /**
     * @param class-string $serviceName
     * @param class-string $serviceInterfaceName
     * @param class-string $serviceParentName
     */
    #[DataProvider('bindDataProvider')]
    public function testBindMatrix(
        string $serviceName,
        string $serviceInterfaceName,
        string $serviceParentName,
        bool $bindInterfaces,
        bool $bindParent,
    ): void {
        $container = new Container();

        $container->bind(
            class: $serviceName,
            bindInterfaces: $bindInterfaces,
            bindParent: $bindParent,
        );

        self::assertSame($container->isAlias($serviceInterfaceName), $bindInterfaces);
        self::assertSame($container->isAlias($serviceParentName), $bindParent);
    }

    public function testBindNoInterface(): void
    {
        $container = new Container();

        $container->bind(ServiceTwo::class);

        self::assertInstanceOf(ServiceTwo::class, $container->resolve(ServiceTwo::class));
    }

    public function testBindLazyService(): void
    {
        $container = new Container();

        $container->bind(LazyService::class);

        self::assertFalse($container->isInitialized(LazyService::class));
        self::assertSame($container->resolve(LazyService::class)->name, 'baz');

        // $container->resolve(LazyService::class);

        // @todo Investigate this, as it uses ->isBound() underneath
        // self::assertTrue($container->isInitialized(LazyService::class));
    }

    public function testResolveWithLazyAndPersistent(): void
    {
        $container = new Container();

        $container->lazy(
            class: PersistentService::class,
            initializer: static fn (): PersistentService => new PersistentService(),
        );

        self::assertInstanceOf(PersistentService::class, $container->resolve(PersistentService::class));
    }

    // @todo This case needs work on the Container for rebuilding the cache
    /*
    public function testRebindAffectsSubsequentResolution(): void
    {
        $container = new Container();

        $container->bind(RebindA::class);
        self::assertTrue($container->isBound(RebindA::class));
        self::assertFalse($container->isInitialized(RebindA::class));

        $first = $container->resolve(RebindC::class);

        self::assertInstanceOf(RebindA::class, $first->subService);
        self::assertFalse($container->isInitialized(RebindA::class));

        $container->bind(RebindB::class);
        self::assertTrue($container->isBound(RebindB::class));
        self::assertFalse($container->isInitialized(RebindB::class));

        $second = $container->resolve(RebindC::class);

        self::assertInstanceOf(RebindB::class, $second->subService);
        self::assertNotSame($first->subService::class, $second->subService::class);
    }
    */

    // @todo Resolve() with something that has a constructor with arguments
    // @todo Resolve() with something that has a constructor without arguments
    // @todo Resolve() with DependencyResolverInterface
    // @todo Resolve() with DependencyResolverInterface with unresolvable type
    // @todo Resolve() with DependencyResolverInterface with type with nullable
    // @todo Resolve() with DependencyResolverInterface with type with default value
    // @todo Resolve() with DependencyResolverInterface with union type
    // @todo Resolve() with DependencyResolverInterface with intersection type
    // @todo Resolve() without DependencyResolverInterface but with scalar type
    // @todo Resolve() with DependencyResolverInterface without type
    // @todo lazy() matrix
    // @todo lazy() with LazyInitializableInterface
    // @todo call() without arguments
    // @todo call() with named arguments
    // @todo call() with indexed arguments
    // @todo call() with mixed arguments
    // @todo isInitialized()
    // @todo Test with anonymous-classes
}
