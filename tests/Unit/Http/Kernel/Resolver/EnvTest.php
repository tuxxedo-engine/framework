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

use Fixture\Env\Profile;
use PHPUnit\Framework\TestCase;
use Support\Env\Source\StubEnvSource;
use Support\Reflection\StubParameterReflector;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerException;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Env\Env;
use Tuxxedo\Env\EnvException;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Http\Kernel\Resolver\Env as EnvResolver;

class EnvTest extends TestCase
{
    /**
     * @param array<string, string|int|float|bool> $values
     */
    private function containerWith(
        array $values,
    ): Container {
        $container = new Container();

        $container->singleton(
            class: new Env(
                new StubEnvSource(
                    values: $values,
                ),
            ),
        );

        return $container;
    }

    public function testResolvesStringParameter(): void
    {
        $resolver = new EnvResolver(
            key: 'FOO',
        );

        self::assertSame(
            'bar',
            $resolver->resolve(
                container: $this->containerWith(
                    values: [
                        'FOO' => 'bar',
                    ],
                ),
                parameter: new StubParameterReflector(
                    builtinType: 'string',
                ),
            ),
        );
    }

    public function testResolvesIntParameter(): void
    {
        $resolver = new EnvResolver(
            key: 'PORT',
        );

        self::assertSame(
            8080,
            $resolver->resolve(
                container: $this->containerWith(
                    values: [
                        'PORT' => 8080,
                    ],
                ),
                parameter: new StubParameterReflector(
                    builtinType: 'int',
                ),
            ),
        );
    }

    public function testResolvesBoolParameter(): void
    {
        $resolver = new EnvResolver(
            key: 'FLAG',
        );

        self::assertTrue(
            $resolver->resolve(
                container: $this->containerWith(
                    values: [
                        'FLAG' => true,
                    ],
                ),
                parameter: new StubParameterReflector(
                    builtinType: 'bool',
                ),
            ),
        );
    }

    public function testResolvesFloatParameter(): void
    {
        $resolver = new EnvResolver(
            key: 'RATIO',
        );

        self::assertSame(
            3.14,
            $resolver->resolve(
                container: $this->containerWith(
                    values: [
                        'RATIO' => 3.14,
                    ],
                ),
                parameter: new StubParameterReflector(
                    builtinType: 'float',
                ),
            ),
        );
    }

    public function testResolvesEnumParameter(): void
    {
        $resolver = new EnvResolver(
            key: 'PROFILE',
        );

        self::assertSame(
            Profile::PRODUCTION,
            $resolver->resolve(
                container: $this->containerWith(
                    values: [
                        'PROFILE' => 'PRODUCTION',
                    ],
                ),
                parameter: new StubParameterReflector(
                    defaultType: Profile::class,
                ),
            ),
        );
    }

    public function testReturnsExplicitDefaultWhenKeyMissing(): void
    {
        $resolver = new EnvResolver(
            key: 'MISSING',
            default: 'fallback',
        );

        self::assertSame(
            'fallback',
            $resolver->resolve(
                container: $this->containerWith(
                    values: [],
                ),
                parameter: new StubParameterReflector(
                    builtinType: 'string',
                ),
            ),
        );
    }

    public function testReturnsNullWhenKeyMissingAndParameterIsNullable(): void
    {
        $resolver = new EnvResolver(
            key: 'MISSING',
        );

        self::assertNull(
            $resolver->resolve(
                container: $this->containerWith(
                    values: [],
                ),
                parameter: new StubParameterReflector(
                    builtinType: 'string',
                    nullable: true,
                ),
            ),
        );
    }

    public function testThrowsWhenKeyMissingAndParameterIsRequired(): void
    {
        $resolver = new EnvResolver(
            key: 'MISSING',
        );

        $this->expectException(EnvException::class);

        $resolver->resolve(
            container: $this->containerWith(
                values: [],
            ),
            parameter: new StubParameterReflector(
                builtinType: 'string',
            ),
        );
    }

    public function testWrapsContainerExceptionAsFromUnboundEnv(): void
    {
        $container = new Container();

        $container->singletonLazy(
            class: EnvInterface::class,
            initializer: static function (ContainerInterface $container): EnvInterface {
                throw ContainerException::fromUnresolvableType();
            },
        );

        $resolver = new EnvResolver(
            key: 'FOO',
        );

        try {
            $resolver->resolve(
                container: $container,
                parameter: new StubParameterReflector(
                    builtinType: 'string',
                ),
            );

            self::fail('Expected EnvException');
        } catch (EnvException $exception) {
            self::assertStringContainsString(
                'EnvInterface',
                $exception->getMessage(),
            );
            self::assertInstanceOf(
                ContainerException::class,
                $exception->getPrevious(),
            );
        }
    }

    public function testThrowsForUnsupportedParameterType(): void
    {
        $resolver = new EnvResolver(
            key: 'FOO',
        );

        $this->expectException(EnvException::class);

        $resolver->resolve(
            container: $this->containerWith(
                values: [
                    'FOO' => 'bar',
                ],
            ),
            parameter: new StubParameterReflector(),
        );
    }
}
