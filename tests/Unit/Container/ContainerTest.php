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
use Fixtures\Container\CtorArgsService;
use Fixtures\Container\CtorNoArgsService;
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
use Tuxxedo\Container\ContainerException;

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

    public function testSealingBind(): void
    {
        $container = new Container();

        $container->bind(ServiceOne::class);
        self::assertFalse($container->sealed);

        $container->seal();
        self::assertTrue($container->sealed);

        $this->expectException(ContainerException::class);
        $container->bind(ServiceTwo::class);
    }

    public function testSealingLazy(): void
    {
        $container = new Container();

        $container->seal();
        $this->expectException(ContainerException::class);
        $container->lazy(
            class: PersistentService::class,
            initializer: static fn (): PersistentService => new PersistentService(),
        );
    }

    public function testAmbiguousInitializer(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $container->lazy(
            class: LazyService::class,
            initializer: static fn (): LazyService => new LazyService(
                name: 'Bug',
            ),
        );
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

    public function testResolveWithConstructorNoArgs(): void
    {
        $container = new Container();
        $service = $container->resolve(CtorNoArgsService::class);

        self::assertTrue($service->ready);
    }

    public function testResolveWithConstructorArgs(): void
    {
        $container = new Container();
        $service = $container->resolve(CtorArgsService::class);

        self::assertSame($service->dependency->foo(), 'bar');
    }

    public function testLazyAnonymousClass(): void
    {
        $container = new Container();

        $container->lazy(
            ServiceOneInterface::class,
            static fn (): ServiceOneInterface => new class () implements ServiceOneInterface {
                public function foo(): string
                {
                    return 'baz';
                }
            },
        );

        self::assertSame($container->resolve(ServiceOneInterface::class)->foo(), 'baz');
    }

    // @todo Resolve() with DependencyResolverInterface
    // @todo Resolve() with DependencyResolverInterface with unresolvable type
    // @todo Resolve() with DependencyResolverInterface with type with nullable
    // @todo Resolve() with DependencyResolverInterface with type with default value
    // @todo Resolve() with DependencyResolverInterface with union type
    // @todo Resolve() with DependencyResolverInterface with intersection type
    // @todo Resolve() without DependencyResolverInterface but with scalar type
    // @todo Resolve() with DependencyResolverInterface without type
    // @todo lazy() matrix
    // @todo call() without arguments
    // @todo call() with named arguments
    // @todo call() with indexed arguments
    // @todo call() with mixed arguments
    // @todo Test with anonymous-classes
}
