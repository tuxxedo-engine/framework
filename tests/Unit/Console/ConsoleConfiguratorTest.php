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

namespace Unit\Console;

use Fixture\Console\Commands\SimpleCommand;
use Fixture\Console\ConsoleConfigurator\ServiceMarker;
use PHPUnit\Framework\TestCase;
use Support\Console\Kernel\RecordingErrorHandler;
use Support\Console\Middleware\RecordingCommandMiddleware;
use Tuxxedo\Application\Environment;
use Tuxxedo\Console\Config\HelpConfig;
use Tuxxedo\Console\Config\SuggestionPolicy;
use Tuxxedo\Console\ConsoleConfigurator;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Input\InputInterface;
use Tuxxedo\Console\Kernel\CommandRegistry;
use Tuxxedo\Console\Kernel\CommandRegistryInterface;
use Tuxxedo\Console\Kernel\HelpFormatter;
use Tuxxedo\Console\Kernel\HelpFormatterInterface;
use Tuxxedo\Console\Kernel\Kernel;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Console\Output\ConsoleOutputInterface;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Container\Container;

class ConsoleConfiguratorTest extends TestCase
{
    private const string SERVICE_FILE = __DIR__ . '/../../Fixture/Console/ConsoleConfigurator/service.php';

    protected function setUp(): void
    {
        parent::setUp();

        ServiceMarker::reset();
    }

    public function testCreateFactoryUsesGivenContainer(): void
    {
        $container = new Container();

        $configurator = ConsoleConfigurator::create(
            container: $container,
        );

        self::assertSame($container, $configurator->container);
    }

    public function testWithAppNameStoresName(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withAppName('demo');

        self::assertSame('demo', $configurator->appName);
    }

    public function testWithAppVersionStoresVersion(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withAppVersion('1.2.3');

        self::assertSame('1.2.3', $configurator->appVersion);
    }

    public function testWithAppEnvironmentStoresEnvironment(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withAppEnvironment(Environment::STAGING);

        self::assertSame(Environment::STAGING, $configurator->appEnvironment);
    }

    public function testWithDefaultCommandDiscoveryStoresDirectoryAndNamespace(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withDefaultCommandDiscovery(
                directory: '/tmp',
                baseNamespace: '\App\Commands',
            );

        self::assertSame('/tmp', $configurator->discoveryDirectory);
        self::assertSame('\App\Commands', $configurator->discoveryBaseNamespace);
    }

    public function testWithCommandClassAppends(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withCommandClass(SimpleCommand::class);

        self::assertSame(
            [
                SimpleCommand::class,
            ],
            $configurator->commandClasses,
        );
    }

    public function testWithoutCommandClassesResetsBothManualAndDiscoveredSources(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withCommandClass(SimpleCommand::class)
            ->withDefaultCommandDiscovery(
                directory: '/tmp',
                baseNamespace: '\App\Commands',
            )
            ->withoutCommandClasses();

        self::assertSame([], $configurator->commandClasses);
        self::assertNull($configurator->discoveryDirectory);
        self::assertNull($configurator->discoveryBaseNamespace);
    }

    public function testWithExceptionHandlerAppendsToPerClassBucket(): void
    {
        $handler = new RecordingErrorHandler();

        $configurator = ConsoleConfigurator::create()
            ->withExceptionHandler(
                exceptionClass: \RuntimeException::class,
                handler: $handler,
            );

        self::assertArrayHasKey(\RuntimeException::class, $configurator->exceptionHandlers);
        self::assertSame(
            [
                $handler,
            ],
            $configurator->exceptionHandlers[\RuntimeException::class],
        );
    }

    public function testWithoutExceptionHandlersClearsAll(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withExceptionHandler(
                exceptionClass: \RuntimeException::class,
                handler: new RecordingErrorHandler(),
            )
            ->withoutExceptionHandlers();

        self::assertSame([], $configurator->exceptionHandlers);
    }

    public function testWithDefaultExceptionHandlerAppends(): void
    {
        $handler = new RecordingErrorHandler();

        $configurator = ConsoleConfigurator::create()
            ->withDefaultExceptionHandler($handler);

        self::assertSame(
            [
                $handler,
            ],
            $configurator->defaultExceptionHandlers,
        );
    }

    public function testWithoutDefaultExceptionHandlersClearsAll(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withDefaultExceptionHandler(new RecordingErrorHandler())
            ->withoutDefaultExceptionHandlers();

        self::assertSame([], $configurator->defaultExceptionHandlers);
    }

    public function testWithMiddlewareAppends(): void
    {
        $middleware = new RecordingCommandMiddleware();

        $configurator = ConsoleConfigurator::create()
            ->withMiddleware($middleware);

        self::assertSame(
            [
                $middleware,
            ],
            $configurator->middleware,
        );
    }

    public function testWithoutMiddlewareClearsAll(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withMiddleware(new RecordingCommandMiddleware())
            ->withoutMiddleware();

        self::assertSame([], $configurator->middleware);
    }

    public function testWithSuggestionPolicyStoresOverride(): void
    {
        $policy = SuggestionPolicy::disabled();

        $configurator = ConsoleConfigurator::create()
            ->withSuggestionPolicy($policy);

        self::assertSame($policy, $configurator->suggestionPolicyOverride);
    }

    public function testWithHelpConfigStoresOverride(): void
    {
        $config = new HelpConfig(
            tokens: [
                '/?',
            ],
        );

        $configurator = ConsoleConfigurator::create()
            ->withHelpConfig($config);

        self::assertSame($config, $configurator->helpConfigOverride);
    }

    public function testWithHelpFormatterStoresFormatter(): void
    {
        $formatter = new HelpFormatter();

        $configurator = ConsoleConfigurator::create()
            ->withHelpFormatter($formatter);

        self::assertSame($formatter, $configurator->helpFormatter);
    }

    public function testWithAppNameHeaderWithoutLabelEnablesAutoResolution(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withAppNameHeader();

        self::assertTrue($configurator->appNameHeaderEnabled);
        self::assertNull($configurator->appNameHeaderLabel);
    }

    public function testWithAppNameHeaderWithEmptyLabelNormalisesToNull(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withAppNameHeader(label: '');

        self::assertTrue($configurator->appNameHeaderEnabled);
        self::assertNull($configurator->appNameHeaderLabel);
    }

    public function testWithAppNameHeaderWithExplicitLabelStoresIt(): void
    {
        $configurator = ConsoleConfigurator::create()
            ->withAppNameHeader(label: 'my-cli');

        self::assertTrue($configurator->appNameHeaderEnabled);
        self::assertSame('my-cli', $configurator->appNameHeaderLabel);
    }

    public function testBuildReturnsKernelAndBindsRuntimeServicesInContainer(): void
    {
        $container = new Container();

        $kernel = ConsoleConfigurator::create(
            container: $container,
        )
            ->withAppEnvironment(Environment::TESTING)
            ->build();

        self::assertInstanceOf(Kernel::class, $kernel);
        self::assertInstanceOf(KernelInterface::class, $kernel);

        self::assertSame(Environment::TESTING, $kernel->appEnvironment);

        self::assertInstanceOf(
            SuggestionPolicy::class,
            $container->resolve(SuggestionPolicy::class),
        );

        self::assertInstanceOf(
            HelpConfig::class,
            $container->resolve(HelpConfig::class),
        );

        self::assertInstanceOf(
            OutputInterface::class,
            $container->resolve(OutputInterface::class),
        );

        self::assertInstanceOf(
            InputInterface::class,
            $container->resolve(InputInterface::class),
        );

        self::assertInstanceOf(
            ConsoleOutputInterface::class,
            $container->resolve(ConsoleOutputInterface::class),
        );

        self::assertInstanceOf(
            CommandRegistry::class,
            $container->resolve(CommandRegistryInterface::class),
        );
    }

    public function testBuildAppliesSuggestionPolicyOverride(): void
    {
        $container = new Container();
        $override = SuggestionPolicy::disabled();

        ConsoleConfigurator::create(
            container: $container,
        )
            ->withSuggestionPolicy($override)
            ->build();

        self::assertSame(
            $override,
            $container->resolve(SuggestionPolicy::class),
        );
    }

    public function testBuildAppliesHelpConfigOverride(): void
    {
        $container = new Container();
        $override = new HelpConfig(
            tokens: [
                '/?',
            ],
        );

        ConsoleConfigurator::create(
            container: $container,
        )
            ->withHelpConfig($override)
            ->build();

        self::assertSame(
            $override,
            $container->resolve(HelpConfig::class),
        );
    }

    public function testBuildBindsCustomHelpFormatterAsSingleton(): void
    {
        $container = new Container();
        $formatter = new HelpFormatter();

        ConsoleConfigurator::create(
            container: $container,
        )
            ->withHelpFormatter($formatter)
            ->build();

        self::assertSame(
            $formatter,
            $container->resolve(HelpFormatterInterface::class),
        );
    }

    public function testBuildDiscoversCommandsFromWithCommandClass(): void
    {
        $container = new Container();

        ConsoleConfigurator::create(
            container: $container,
        )
            ->withCommandClass(SimpleCommand::class)
            ->build();

        $registry = $container->resolve(CommandRegistryInterface::class);

        self::assertNotNull(
            $registry->find(
                path: [
                    'demo:simple',
                ],
            ),
        );
    }

    public function testBuildDiscoversCommandsFromDefaultDiscoveryDirectory(): void
    {
        $container = new Container();

        ConsoleConfigurator::create(
            container: $container,
        )
            ->withDefaultCommandDiscovery(
                directory: __DIR__ . '/../../Fixture/Console/DiscoveryFixtures',
                baseNamespace: '\Fixture\Console\DiscoveryFixtures\\',
            )
            ->build();

        $registry = $container->resolve(CommandRegistryInterface::class);

        self::assertNotNull(
            $registry->find(
                path: [
                    'demo:discovered',
                ],
            ),
        );
    }

    public function testBuildThrowsWhenDiscoveryDirectoryDoesNotExist(): void
    {
        $this->expectException(ConsoleException::class);

        ConsoleConfigurator::create()
            ->withDefaultCommandDiscovery(
                directory: '/path/that/does/not/exist/anywhere',
                baseNamespace: '\App\Commands\\',
            )
            ->build();
    }

    public function testBuildPropagatesExceptionHandlersToKernel(): void
    {
        $handler = new RecordingErrorHandler();

        $kernel = ConsoleConfigurator::create()
            ->withExceptionHandler(
                exceptionClass: \RuntimeException::class,
                handler: $handler,
            )
            ->withDefaultExceptionHandler($handler)
            ->build();

        self::assertArrayHasKey(\RuntimeException::class, $kernel->exceptionHandlers);
        self::assertNotEmpty($kernel->defaultExceptionHandlers);
    }

    public function testBuildPropagatesMiddlewareToKernel(): void
    {
        $middleware = new RecordingCommandMiddleware();

        $kernel = ConsoleConfigurator::create()
            ->withMiddleware($middleware)
            ->build();

        self::assertNotEmpty($kernel->middleware);
    }

    public function testBuildInvokesServiceFileClosureWithContainer(): void
    {
        ConsoleConfigurator::create()
            ->withServiceFile(file: self::SERVICE_FILE)
            ->build();

        self::assertCount(1, ServiceMarker::$invocations);
        self::assertSame(Container::class, ServiceMarker::$invocations[0]);
    }

    public function testBuildInvokesEachServiceFileInOrder(): void
    {
        ConsoleConfigurator::create()
            ->withServiceFile(file: self::SERVICE_FILE)
            ->withServiceFile(file: self::SERVICE_FILE)
            ->build();

        self::assertCount(2, ServiceMarker::$invocations);
    }

    public function testBuildPropagatesAppNameHeaderStateToKernel(): void
    {
        $kernel = ConsoleConfigurator::create()
            ->withAppName('engine-demo')
            ->withAppNameHeader(label: 'my-cli')
            ->build();

        self::assertInstanceOf(Kernel::class, $kernel);
        self::assertTrue($kernel->appNameHeaderEnabled);
        self::assertSame('my-cli', $kernel->appNameHeaderLabel);
        self::assertSame('engine-demo', $kernel->appName);
    }
}
