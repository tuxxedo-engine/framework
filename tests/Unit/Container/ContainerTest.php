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
use Fixtures\Container\DefaultServiceOne;
use Fixtures\Container\DefaultServiceOneInterface;
use Fixtures\Container\DefaultServiceTwo;
use Fixtures\Container\DefaultServiceTwoInterface;
use Fixtures\Container\IntService;
use Fixtures\Container\IntersectionService;
use Fixtures\Container\LazyService;
use Fixtures\Container\NoTypeService;
use Fixtures\Container\OptionalService;
use Fixtures\Container\OptionalWithNullService;
use Fixtures\Container\PersistentService;
use Fixtures\Container\RebindA;
use Fixtures\Container\RebindB;
use Fixtures\Container\RebindC;
use Fixtures\Container\ServiceInterface;
use Fixtures\Container\ServiceOne;
use Fixtures\Container\ServiceOneInterface;
use Fixtures\Container\ServiceTwo;
use Fixtures\Container\StringService;
use Fixtures\Container\UnionService;
use Fixtures\Container\UnresolvableService;
use Fixtures\Container\UnresolvableUnionService;
use Fixtures\Container\UnresolvableWithNullService;
use Fixtures\Container\UnresolvableWithResolverService;
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

        self::assertSame($container->resolve(LazyService::class)->name, 'baz');
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

    public function testResolveWithLazyWithNoAliasing(): void
    {
        $container = new Container();

        $container->lazy(
            class: PersistentService::class,
            initializer: static fn (): PersistentService => new PersistentService(),
            bindInterfaces: false,
            bindParent: false,
        );

        self::assertInstanceOf(PersistentService::class, $container->resolve(PersistentService::class));
    }

    public function testRebindAffectsSubsequentResolution(): void
    {
        $container = new Container();

        $container->bind(RebindA::class);
        self::assertTrue($container->isBound(RebindA::class));

        $first = $container->resolve(RebindC::class);

        self::assertInstanceOf(RebindA::class, $first->subService);

        $container->bind(RebindB::class);
        self::assertTrue($container->isBound(RebindB::class));

        $second = $container->resolve(RebindC::class);

        self::assertInstanceOf(RebindB::class, $second->subService);
        self::assertNotSame($first->subService::class, $second->subService::class);
    }

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

    public static function callDataProvider(): \Generator
    {
        yield [
            static fn (): true => true,
            [],
        ];

        yield [
            static fn (int $a, int $b): bool => $a + $b === 3,
            [
                1,
                2,
            ],
        ];

        yield [
            static fn (string $a, string $b): bool => $a . $b === 'foobar',
            [
                'a' => 'foo',
                'b' => 'bar',
            ],
        ];

        yield [
            static fn (ServiceOne $service): bool => $service->foo() === 'bar',
            [],
        ];

        yield [
            static fn (ServiceOneInterface $service): bool => $service->foo() === 'bar',
            [
                'service' => new ServiceOne(),
            ],
        ];

        yield [
            static fn (ServiceOne $service, string $a, string $b): bool => $a . $service->foo() . $b === 'foobarbaz',
            [
                2 => 'baz',
                'a' => 'foo',
            ],
        ];
    }

    /**
     * @param mixed[] $arguments
     */
    #[DataProvider('callDataProvider')]
    public function testCall(
        \Closure $callable,
        array $arguments,
    ): void {
        $container = new Container();

        self::assertTrue($container->call($callable, $arguments));
    }

    public static function resolveWithArgumentsDataProvider(): \Generator
    {
        yield [
            OptionalWithNullService::class,
            [],
            static fn (OptionalWithNullService $service): bool => $service->secret === null,
        ];

        yield [
            IntService::class,
            [
                1,
            ],
            static fn (IntService $service): bool => $service->value === 1,
        ];

        yield [
            LazyService::class,
            [
                'name' => 'foobar',
            ],
            static fn (LazyService $service): bool => $service->name === 'foobar',
        ];

        yield [
            DefaultServiceOneInterface::class,
            [],
            static fn (DefaultServiceOne $service): bool => \str_repeat((string) $service->a, $service->b) === '11',
        ];
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param mixed[] $arguments
     * @param \Closure(TClassName): bool $callable
     */
    #[DataProvider('resolveWithArgumentsDataProvider')]
    public function testResolveWithArguments(
        string $className,
        array $arguments,
        \Closure $callable,
    ): void {
        $container = new Container();

        $service = $container->resolve($className, $arguments);

        self::assertTrue($callable($service));
    }

    public static function invalidResolutionDataProvider(): \Generator
    {
        yield [
            IntService::class,
        ];

        yield [
            NoTypeService::class,
        ];

        yield [
            UnresolvableService::class,
        ];

        yield [
            UnresolvableWithResolverService::class,
        ];

        yield [
            IntersectionService::class,
        ];

        yield [
            UnresolvableUnionService::class,
        ];
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('invalidResolutionDataProvider')]
    public function testResolveScalar(
        string $className,
    ): void {
        $container = new Container();

        self::expectException(ContainerException::class);

        $container->resolve($className);
    }

    public function testResolverString(): void
    {
        $container = new Container();
        $service = $container->resolve(StringService::class);

        self::assertSame($service->value, 'foo');
    }

    public function testResolveUnion(): void
    {
        $container = new Container();
        $unionService = $container->resolve(UnionService::class);

        self::assertInstanceOf(ServiceOne::class, $unionService->service);
        self::assertSame($unionService->service->foo(), 'bar');
    }

    public static function optionalDataProvider(): \Generator
    {
        yield [
            OptionalService::class,
            'phpfi',
        ];

        yield [
            OptionalWithNullService::class,
            null,
        ];

        yield [
            UnresolvableWithNullService::class,
            null,
        ];
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('optionalDataProvider')]
    public function testResolveWithOptionals(
        string $className,
        mixed $value,
    ): void {
        $container = new Container();

        /** @var object{secret: mixed} $service */
        $service = $container->resolve($className);

        self::assertSame($service->secret, $value);
    }

    public static function defaultImplementationDataProvider(): \Generator
    {
        yield [
            DefaultServiceOneInterface::class,
            DefaultServiceOne::class,
        ];

        yield [
            DefaultServiceTwoInterface::class,
            DefaultServiceTwo::class,
        ];
    }

    /**
     * @param class-string $class
     * @param class-string $expectedClass
     */
    #[DataProvider('defaultImplementationDataProvider')]
    public function testDefaultImplementation(
        string $class,
        string $expectedClass,
    ): void {
        $container = new Container();

        $service = $container->resolve($class);

        self::assertInstanceOf($expectedClass, $service);
    }

    public function testDefaultImplementationWithArguments(): void
    {
        $container = new Container();

        $service = $container->resolve(
            DefaultServiceOneInterface::class,
            [
                'a' => 8,
                'b' => 9,
            ],
        );

        self::assertSame($service->a * $service->b, 72);
    }

    public function testDefaultImplementationWithDefaultInitializerAndArguments(): void
    {
        $container = new Container();

        $this->expectException(\Error::class);
        $container->resolve(
            DefaultServiceTwoInterface::class,
            [
                'void',
            ],
        );
    }

    public function testDefaultImplementationNoAttribute(): void
    {
        $container = new Container();

        $this->expectException(\Error::class);
        $container->resolve(ServiceInterface::class);
    }
}
