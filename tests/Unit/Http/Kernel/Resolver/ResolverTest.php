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
use Support\Http\Kernel\StubDispatcher;
use Support\Http\Response\StubResponseEmitter;
use Tuxxedo\Application\Profile;
use Tuxxedo\Config\Config;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Kernel\Resolver\App;
use Tuxxedo\Http\Kernel\Resolver\AppName;
use Tuxxedo\Http\Kernel\Resolver\AppProfile;
use Tuxxedo\Http\Kernel\Resolver\AppUrl;
use Tuxxedo\Http\Kernel\Resolver\AppVersion;
use Tuxxedo\Http\Kernel\Resolver\ConfigValue;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Reflection\ParameterReflectorInterface;
use Tuxxedo\Router\StaticRouter;

class ResolverTest extends TestCase
{
    private function makeContainer(
        string $appName = '',
        string $appVersion = '',
        Profile $appProfile = Profile::RELEASE,
        string $appUrl = '',
        Config $config = new Config(),
    ): Container {
        $container = new Container();

        $container->persistent(
            class: new Kernel(
                container: $container,
                config: $config,
                emitter: new StubResponseEmitter(),
                dispatcher: new StubDispatcher(new Response()),
                router: new StaticRouter(
                    routes: [],
                ),
                appName: $appName,
                appVersion: $appVersion,
                appProfile: $appProfile,
                appUrl: $appUrl,
            ),
        );

        return $container;
    }

    private function parameter(): ParameterReflectorInterface
    {
        return new class () implements ParameterReflectorInterface {
            public \ReflectionParameter $reflector {
                get {
                    throw new \LogicException('Not implemented in stub');
                }
            }

            public string $name {
                get {
                    return 'stub';
                }
            }

            public function getDefaultType(): ?string
            {
                return null;
            }

            public function getBuiltinType(): ?string
            {
                return null;
            }

            public function isNullable(): bool
            {
                return false;
            }

            public function hasAttribute(
                string $attribute,
            ): bool {
                return false;
            }

            public function getAttribute(
                string $attribute,
            ): object {
                throw new \LogicException('Not implemented in stub');
            }

            public function getAttributes(
                string $attribute,
            ): \Generator {
                return (static function (): \Generator {
                    yield from [];
                })();
            }
        };
    }

    public function testAppResolvesKernel(): void
    {
        $container = $this->makeContainer();

        $result = (new App())->resolve($container, $this->parameter());

        self::assertInstanceOf(KernelInterface::class, $result);
    }

    public function testAppNameResolvesAppName(): void
    {
        $container = $this->makeContainer(
            appName: 'Tuxxedo Engine',
        );

        self::assertSame('Tuxxedo Engine', (new AppName())->resolve($container, $this->parameter()));
    }

    public function testAppVersionResolvesAppVersion(): void
    {
        $container = $this->makeContainer(
            appVersion: '1.2.3',
        );

        self::assertSame('1.2.3', (new AppVersion())->resolve($container, $this->parameter()));
    }

    public function testAppProfileResolvesAppProfile(): void
    {
        $container = $this->makeContainer(
            appProfile: Profile::DEBUG,
        );

        self::assertSame(Profile::DEBUG, (new AppProfile())->resolve($container, $this->parameter()));
    }

    public function testAppUrlResolvesAppUrl(): void
    {
        $container = $this->makeContainer(
            appUrl: 'https://tuxxedo.dev',
        );

        self::assertSame('https://tuxxedo.dev', (new AppUrl())->resolve($container, $this->parameter()));
    }

    public function testConfigValueResolvesConfigPath(): void
    {
        $config = new Config(
            directives: [
                'app' => [
                    'name' => 'Tuxxedo Engine',
                ],
            ],
        );

        $container = $this->makeContainer(
            config: $config,
        );

        self::assertSame('Tuxxedo Engine', (new ConfigValue('app.name'))->resolve($container, $this->parameter()));
    }
}
