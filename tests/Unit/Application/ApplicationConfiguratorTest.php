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

namespace Unit\Application;

use Fixture\Application\ApplicationConfigurator\ServiceMarker;
use PHPUnit\Framework\TestCase;
use Support\Database\StubConnectionManager;
use Support\Env\Source\StubEnvSource;
use Support\File\Storage\StubStorage;
use Support\Http\Kernel\StubDispatcher;
use Support\Http\Request\Middleware\RecordingMiddleware;
use Support\Http\Response\StubResponseEmitter;
use Support\Mail\Transport\RecordingMailTransport;
use Tuxxedo\Application\ApplicationConfigurator;
use Tuxxedo\Application\Profile;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigException;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Env\Env;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Event\EventsManager;
use Tuxxedo\Event\EventsManagerInterface;
use Tuxxedo\File\Storage\Local\Config\LocalStorageConfig;
use Tuxxedo\File\Storage\Local\LocalStorage;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Http\Kernel\DispatcherInterface;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Mail\Config\MailManagerConfig;
use Tuxxedo\Mail\MailManager;
use Tuxxedo\Mail\MailManagerConfiguratorInterface;
use Tuxxedo\Mail\MailManagerInterface;
use Tuxxedo\Mail\Transport\FileMail\Config\FileMailTransportConfig;
use Tuxxedo\Mail\Transport\FileMail\FileMailTransport;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Router\RouteDiscoverer;
use Tuxxedo\Router\RouterInterface;
use Tuxxedo\Router\StaticRouter;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;
use Tuxxedo\View\ViewRenderInterface;

class ApplicationConfiguratorTest extends TestCase
{
    private const string CONFIG_FILE = __DIR__ . '/../../Fixture/Application/ApplicationConfigurator/app.php';
    private const string CONFIG_DIRECTORY = __DIR__ . '/../../Fixture/Application/ApplicationConfigurator/directory';
    private const string SERVICE_FILE = __DIR__ . '/../../Fixture/Application/ApplicationConfigurator/service.php';
    private const string SERVICE_NON_CLOSURE_FILE = __DIR__ . '/../../Fixture/Application/ApplicationConfigurator/service-non-closure.php';
    private const string NO_APP_CONFIG_FILE = __DIR__ . '/../../Fixture/Application/ApplicationConfigurator/no-app-config.php';

    protected function setUp(): void
    {
        ServiceMarker::reset();
    }

    public function testConstructorAcceptsExplicitAppMetadata(): void
    {
        $configurator = new ApplicationConfigurator(
            appName: 'MyApp',
            appVersion: '9.9.9',
            appProfile: Profile::DEBUG,
            appUrl: 'https://my.app/',
        );

        self::assertSame('MyApp', $configurator->appName);
        self::assertSame('9.9.9', $configurator->appVersion);
        self::assertSame(Profile::DEBUG, $configurator->appProfile);
        self::assertSame('https://my.app/', $configurator->appUrl);
    }

    public function testCreateFromConfigFilePopulatesAppMetadataFromConfig(): void
    {
        $configurator = ApplicationConfigurator::createFromConfigFile(self::CONFIG_FILE);

        self::assertSame('TuxxedoTestApp', $configurator->appName);
        self::assertSame('1.2.3', $configurator->appVersion);
        self::assertSame(Profile::RELEASE, $configurator->appProfile);
        self::assertSame('https://example.test/', $configurator->appUrl);
    }

    public function testCreateFromConfigFileInjectsContainerAndConfig(): void
    {
        $configurator = ApplicationConfigurator::createFromConfigFile(self::CONFIG_FILE);

        self::assertInstanceOf(Container::class, $configurator->container);
        self::assertInstanceOf(Config::class, $configurator->config);
    }

    public function testCreateFromConfigFileRegistersEnv(): void
    {
        $configurator = ApplicationConfigurator::createFromConfigFile(
            file: self::CONFIG_FILE,
            env: new Env(
                new StubEnvSource([]),
            ),
        );

        /** @var ContainerInterface $container */
        $container = $configurator->container;

        self::assertTrue($container->isBound(EnvInterface::class));
    }

    public function testCreateFromConfigDirectoryPopulatesAppMetadataFromConfig(): void
    {
        $configurator = ApplicationConfigurator::createFromConfigDirectory(self::CONFIG_DIRECTORY);

        self::assertSame('TuxxedoDirApp', $configurator->appName);
        self::assertSame('4.5.6', $configurator->appVersion);
        self::assertSame(Profile::DEBUG, $configurator->appProfile);
        self::assertSame('https://example.dir/', $configurator->appUrl);
    }

    public function testCreateFromConfigDirectoryInjectsContainerAndConfig(): void
    {
        $configurator = ApplicationConfigurator::createFromConfigDirectory(self::CONFIG_DIRECTORY);

        self::assertInstanceOf(Container::class, $configurator->container);
        self::assertInstanceOf(Config::class, $configurator->config);
    }


    public function testCreateFromConfigDirectoryRegistersEnv(): void
    {
        $configurator = ApplicationConfigurator::createFromConfigDirectory(
            directory: self::CONFIG_DIRECTORY,
            env: new Env(
                new StubEnvSource([]),
            ),
        );

        /** @var ContainerInterface $container */
        $container = $configurator->container;

        self::assertTrue($container->isBound(EnvInterface::class));
    }

    public function testCreateFromConfigFileThrowsConfigExceptionWhenAppConfigIsMissing(): void
    {
        self::expectException(ConfigException::class);

        ApplicationConfigurator::createFromConfigFile(self::NO_APP_CONFIG_FILE);
    }

    public function testWithAppNameUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withAppName(
            name: 'Renamed',
        );

        self::assertSame('Renamed', $configurator->appName);
        self::assertSame($configurator, $result);
    }

    public function testWithAppVersionUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withAppVersion(
            version: '7.7.7',
        );

        self::assertSame('7.7.7', $configurator->appVersion);
        self::assertSame($configurator, $result);
    }

    public function testWithAppProfileUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withAppProfile(
            profile: Profile::DEBUG,
        );

        self::assertSame(Profile::DEBUG, $configurator->appProfile);
        self::assertSame($configurator, $result);
    }

    public function testWithAppUrlUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withAppUrl(
            url: 'https://updated.test/',
        );

        self::assertSame('https://updated.test/', $configurator->appUrl);
        self::assertSame($configurator, $result);
    }

    public function testWithDefaultRouterStoresDirectoryAndBaseNamespace(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withDefaultRouter(
            directory: '/path/to/controllers',
            baseNamespace: '\App\Foo\\',
            strictMode: false,
        );

        self::assertSame('/path/to/controllers', $configurator->defaultRouterDirectory);
        self::assertSame('\App\Foo\\', $configurator->defaultRouterBaseNamespace);
        self::assertFalse($configurator->defaultRouterStrictMode);
    }

    public function testWithDefaultRouterDefaultsBaseNamespaceAndStrictMode(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withDefaultRouter(
            directory: '/path/to/controllers',
        );

        self::assertSame('\App\Controllers\\', $configurator->defaultRouterBaseNamespace);
        self::assertTrue($configurator->defaultRouterStrictMode);
    }

    public function testWithDefaultRouterClearsExplicitRouter(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withRouter(
            router: new StaticRouter(
                routes: [],
            ),
        );

        $configurator->withDefaultRouter(
            directory: '/path/to/controllers',
        );

        self::assertNull($configurator->router);
    }

    public function testWithDefaultRouterReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withDefaultRouter(
            directory: '/path/to/controllers',
        );

        self::assertSame($configurator, $result);
    }

    public function testWithRouterUpdatesPropertyAndClearsDefaultRouterFields(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultRouter(
            directory: '/path/to/controllers',
            baseNamespace: '\App\Custom\\',
        );

        $router = new StaticRouter(
            routes: [],
        );
        $result = $configurator->withRouter(
            router: $router,
        );

        self::assertSame($router, $configurator->router);
        self::assertNull($configurator->defaultRouterDirectory);
        self::assertNull($configurator->defaultRouterBaseNamespace);
        self::assertSame($configurator, $result);
    }

    public function testWithEmitterUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $emitter = new StubResponseEmitter();
        $result = $configurator->withEmitter(
            emitter: $emitter,
        );

        self::assertSame($emitter, $configurator->emitter);
        self::assertSame($configurator, $result);
    }

    public function testWithDispatcherUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $dispatcher = new StubDispatcher(
            result: new Response(),
        );

        $result = $configurator->withDispatcher(
            dispatcher: $dispatcher,
        );

        self::assertSame($dispatcher, $configurator->dispatcher);
        self::assertSame($configurator, $result);
    }

    public function testWithEventsManagerUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $eventsManager = new EventsManager(
            container: new Container(),
        );

        $result = $configurator->withEventsManager(
            eventsManager: $eventsManager,
        );

        self::assertSame($eventsManager, $configurator->eventsManager);
        self::assertSame($configurator, $result);
    }

    public function testWithUrlUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $url = new Url(
            base: 'https://example.test/',
        );

        $result = $configurator->withUrl(
            url: $url,
        );

        self::assertSame($url, $configurator->url);
        self::assertSame($configurator, $result);
    }

    public function testWithLumiUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $lumi = new LumiConfigurator(
            container: new Container(),
        );

        $result = $configurator->withLumi(
            lumiConfigurator: $lumi,
        );

        self::assertSame($lumi, $configurator->lumiConfigurator);
        self::assertSame($configurator, $result);
    }

    public function testWithLumiClearsDefaultLumiState(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultLumi(
            customizer: static fn (LumiConfiguratorInterface $lumi): LumiConfiguratorInterface => $lumi,
        );

        $configurator->withLumi(
            lumiConfigurator: new LumiConfigurator(
                container: new Container(),
            ),
        );

        self::assertFalse($configurator->useDefaultLumi);
        self::assertNull($configurator->lumiCustomizer);
    }

    public function testWithDefaultLumiEnablesUseDefaultLumiAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();

        $result = $configurator->withDefaultLumi();

        self::assertTrue($configurator->useDefaultLumi);
        self::assertSame($configurator, $result);
    }

    public function testWithDefaultLumiStoresCustomizer(): void
    {
        $configurator = new ApplicationConfigurator();
        $customizer = static fn (LumiConfiguratorInterface $lumi): LumiConfiguratorInterface => $lumi;

        $configurator->withDefaultLumi(
            customizer: $customizer,
        );

        self::assertSame($customizer, $configurator->lumiCustomizer);
    }

    public function testWithDefaultLumiNoArgClearsCustomizer(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultLumi(
            customizer: static fn (LumiConfiguratorInterface $lumi): LumiConfiguratorInterface => $lumi,
        );

        $configurator->withDefaultLumi();

        self::assertNull($configurator->lumiCustomizer);
    }

    public function testWithDefaultLumiClearsExplicitLumiConfigurator(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withLumi(
            lumiConfigurator: new LumiConfigurator(
                container: new Container(),
            ),
        );

        $configurator->withDefaultLumi();

        self::assertNull($configurator->lumiConfigurator);
    }

    public function testWithConnectionManagerUpdatesPropertyAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $manager = new StubConnectionManager();

        $result = $configurator->withConnectionManager(
            connectionManager: $manager,
        );

        self::assertSame($manager, $configurator->connectionManager);
        self::assertSame($configurator, $result);
    }

    public function testWithConnectionManagerClearsDefaultConnectionManagerState(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultConnectionManager(
            customizer: static fn (ConnectionManagerInterface $manager): ConnectionManagerInterface => $manager,
        );

        $configurator->withConnectionManager(
            connectionManager: new StubConnectionManager(),
        );

        self::assertFalse($configurator->useDefaultConnectionManager);
        self::assertNull($configurator->connectionManagerCustomizer);
    }

    public function testWithDefaultConnectionManagerEnablesUseDefaultConnectionManagerAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();

        $result = $configurator->withDefaultConnectionManager();

        self::assertTrue($configurator->useDefaultConnectionManager);
        self::assertSame($configurator, $result);
    }

    public function testWithDefaultConnectionManagerStoresCustomizer(): void
    {
        $configurator = new ApplicationConfigurator();
        $customizer = static fn (ConnectionManagerInterface $manager): ConnectionManagerInterface => $manager;

        $configurator->withDefaultConnectionManager(
            customizer: $customizer,
        );

        self::assertSame($customizer, $configurator->connectionManagerCustomizer);
    }

    public function testWithDefaultConnectionManagerNoArgClearsCustomizer(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultConnectionManager(
            customizer: static fn (ConnectionManagerInterface $manager): ConnectionManagerInterface => $manager,
        );

        $configurator->withDefaultConnectionManager();

        self::assertNull($configurator->connectionManagerCustomizer);
    }

    public function testWithDefaultConnectionManagerClearsExplicitConnectionManager(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withConnectionManager(
            connectionManager: new StubConnectionManager(),
        );

        $configurator->withDefaultConnectionManager();

        self::assertNull($configurator->connectionManager);
    }

    public function testWithStorageStoresInstanceAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $storage = new StubStorage();

        $result = $configurator->withStorage(
            storage: $storage,
        );

        self::assertSame($storage, $configurator->storage);
        self::assertSame($configurator, $result);
    }

    public function testWithStorageClearsDefaultStorageState(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultStorage();

        $configurator->withStorage(
            storage: new StubStorage(),
        );

        self::assertFalse($configurator->useDefaultStorage);
    }

    public function testWithDefaultStorageEnablesUseDefaultStorageAndReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();

        $result = $configurator->withDefaultStorage();

        self::assertTrue($configurator->useDefaultStorage);
        self::assertSame($configurator, $result);
    }

    public function testWithDefaultStorageClearsExplicitStorage(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withStorage(
            storage: new StubStorage(),
        );

        $configurator->withDefaultStorage();

        self::assertNull($configurator->storage);
    }

    public function testWithMiddlewareWrapsInstanceInClosure(): void
    {
        $configurator = new ApplicationConfigurator();
        $instance = new RecordingMiddleware();

        $configurator->withMiddleware(
            middleware: $instance,
        );

        self::assertCount(1, $configurator->middleware);
        self::assertInstanceOf(\Closure::class, $configurator->middleware[0]);
        self::assertSame($instance, ($configurator->middleware[0])());
    }

    public function testWithMiddlewareKeepsClosureAsIs(): void
    {
        $configurator = new ApplicationConfigurator();
        $closure = static fn (): MiddlewareInterface => new RecordingMiddleware();

        $configurator->withMiddleware(
            middleware: $closure,
        );

        self::assertSame($closure, $configurator->middleware[0]);
    }

    public function testWithMiddlewareAppendsMultipleEntries(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withMiddleware(
            middleware: new RecordingMiddleware(),
        );
        $configurator->withMiddleware(
            middleware: new RecordingMiddleware(),
        );

        self::assertCount(2, $configurator->middleware);
    }

    public function testWithMiddlewareReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withMiddleware(
            middleware: new RecordingMiddleware(),
        );

        self::assertSame($configurator, $result);
    }

    public function testWithoutMiddlewareClearsMiddlewareArray(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withMiddleware(
            middleware: new RecordingMiddleware(),
        );

        $configurator->withMiddleware(
            middleware: new RecordingMiddleware(),
        );

        $configurator->withoutMiddleware();

        self::assertSame([], $configurator->middleware);
    }

    public function testWithoutMiddlewareReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withoutMiddleware();

        self::assertSame($configurator, $result);
    }

    public function testWithExceptionHandlerWrapsInstanceInClosure(): void
    {
        $configurator = new ApplicationConfigurator();
        $handler = self::makeErrorHandler();

        $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: $handler,
        );

        self::assertArrayHasKey(\RuntimeException::class, $configurator->exceptionHandlers);
        self::assertCount(1, $configurator->exceptionHandlers[\RuntimeException::class]);
        self::assertInstanceOf(\Closure::class, $configurator->exceptionHandlers[\RuntimeException::class][0]);
        self::assertSame($handler, ($configurator->exceptionHandlers[\RuntimeException::class][0])());
    }

    public function testWithExceptionHandlerKeepsClosureAsIs(): void
    {
        $configurator = new ApplicationConfigurator();
        $closure = fn (): ErrorHandlerInterface => self::makeErrorHandler();

        $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: $closure,
        );

        self::assertSame($closure, $configurator->exceptionHandlers[\RuntimeException::class][0]);
    }

    public function testWithExceptionHandlerAppendsMultipleHandlersUnderSameClass(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: self::makeErrorHandler(),
        );

        $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: self::makeErrorHandler(),
        );

        self::assertCount(2, $configurator->exceptionHandlers[\RuntimeException::class]);
    }

    public function testWithExceptionHandlerGroupsByExceptionClass(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: self::makeErrorHandler(),
        );

        $configurator->withExceptionHandler(
            exceptionClass: \LogicException::class,
            handler: self::makeErrorHandler(),
        );

        self::assertArrayHasKey(\RuntimeException::class, $configurator->exceptionHandlers);
        self::assertArrayHasKey(\LogicException::class, $configurator->exceptionHandlers);
    }

    public function testWithExceptionHandlerReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: self::makeErrorHandler(),
        );

        self::assertSame($configurator, $result);
    }

    public function testWithoutExceptionHandlersClearsHandlerMap(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: self::makeErrorHandler(),
        );

        $configurator->withoutExceptionHandlers();

        self::assertSame([], $configurator->exceptionHandlers);
    }

    public function testWithoutExceptionHandlersReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withoutExceptionHandlers();

        self::assertSame($configurator, $result);
    }

    public function testWithDefaultExceptionHandlerWrapsInstanceInClosure(): void
    {
        $configurator = new ApplicationConfigurator();
        $handler = self::makeErrorHandler();

        $configurator->withDefaultExceptionHandler(
            handler: $handler,
        );

        self::assertCount(1, $configurator->defaultExceptionHandlers);
        self::assertInstanceOf(\Closure::class, $configurator->defaultExceptionHandlers[0]);
        self::assertSame($handler, ($configurator->defaultExceptionHandlers[0])());
    }

    public function testWithDefaultExceptionHandlerKeepsClosureAsIs(): void
    {
        $configurator = new ApplicationConfigurator();
        $closure = fn (): ErrorHandlerInterface => self::makeErrorHandler();

        $configurator->withDefaultExceptionHandler(
            handler: $closure,
        );

        self::assertSame($closure, $configurator->defaultExceptionHandlers[0]);
    }

    public function testWithDefaultExceptionHandlerAppendsMultipleEntries(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withDefaultExceptionHandler(
            handler: self::makeErrorHandler(),
        );

        $configurator->withDefaultExceptionHandler(
            handler: self::makeErrorHandler(),
        );

        self::assertCount(2, $configurator->defaultExceptionHandlers);
    }

    public function testWithDefaultExceptionHandlerReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withDefaultExceptionHandler(
            handler: self::makeErrorHandler(),
        );

        self::assertSame($configurator, $result);
    }

    public function testWithoutDefaultExceptionHandlersClearsArray(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultExceptionHandler(
            handler: self::makeErrorHandler(),
        );

        $configurator->withoutDefaultExceptionHandlers();

        self::assertSame([], $configurator->defaultExceptionHandlers);
    }

    public function testWithoutDefaultExceptionHandlersReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withoutDefaultExceptionHandlers();

        self::assertSame($configurator, $result);
    }

    public function testWithDebugHandlerEnablesUseDebugHandler(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withDebugHandler();

        self::assertTrue($configurator->useDebugHandler);
    }

    public function testWithDebugHandlerDefaultsRegisterPhpErrorHandlerToTrue(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withDebugHandler();

        self::assertTrue($configurator->registerPhpErrorHandler);
    }

    public function testWithDebugHandlerHonorsRegisterPhpErrorHandlerArgument(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withDebugHandler(
            registerPhpErrorHandler: false,
        );

        self::assertFalse($configurator->registerPhpErrorHandler);
    }

    public function testWithDebugHandlerReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withDebugHandler();

        self::assertSame($configurator, $result);
    }

    public function testWithoutDebugHandlerDisablesUseDebugHandler(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDebugHandler();

        $configurator->withoutDebugHandler();

        self::assertFalse($configurator->useDebugHandler);
    }

    public function testWithoutDebugHandlerReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withoutDebugHandler();

        self::assertSame($configurator, $result);
    }

    public function testWithServiceFileAppendsToServiceFiles(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withServiceFile(
            file: '/services/a.php',
        );

        $configurator->withServiceFile(
            file: '/services/b.php',
        );

        self::assertSame(
            [
                '/services/a.php',
                '/services/b.php',
            ],
            $configurator->serviceFiles,
        );
    }

    public function testWithServiceFileReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withServiceFile(
            file: '/services/a.php',
        );

        self::assertSame($configurator, $result);
    }

    public function testWithoutServiceFilesClearsServiceFiles(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withServiceFile(
            file: '/services/a.php',
        );

        $configurator->withoutServiceFiles();

        self::assertSame([], $configurator->serviceFiles);
    }

    public function testWithoutServiceFilesReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withoutServiceFiles();

        self::assertSame($configurator, $result);
    }

    private function makeMinimalConfigurator(): ApplicationConfigurator
    {
        return (new ApplicationConfigurator(
            appName: 'MinimalApp',
            appVersion: '0.1.0',
            appProfile: Profile::RELEASE,
            appUrl: 'https://minimal.test/',
            container: new Container(),
            config: new Config(),
        ))
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );
    }

    public function testBuildReturnsKernelInstance(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        self::assertInstanceOf(KernelInterface::class, $configurator->build());
    }

    public function testBuildPropagatesAppMetadataToKernel(): void
    {
        $configurator = $this->makeMinimalConfigurator();
        $kernel = $configurator->build();

        self::assertSame('MinimalApp', $kernel->appName);
        self::assertSame('0.1.0', $kernel->appVersion);
        self::assertSame(Profile::RELEASE, $kernel->appProfile);
        self::assertSame('https://minimal.test/', $kernel->appUrl);
    }

    public function testBuildPropagatesExplicitEmitterToKernel(): void
    {
        $emitter = new StubResponseEmitter();
        $configurator = (new ApplicationConfigurator())
            ->withEmitter(
                emitter: $emitter,
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $kernel = $configurator->build();

        self::assertSame($emitter, $kernel->emitter);
    }

    public function testBuildPropagatesExplicitDispatcherToKernel(): void
    {
        $dispatcher = new StubDispatcher(
            result: new Response(),
        );

        $configurator = (new ApplicationConfigurator())
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: $dispatcher,
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $kernel = $configurator->build();

        self::assertSame($dispatcher, $kernel->dispatcher);
    }

    public function testBuildPropagatesExplicitRouterToKernel(): void
    {
        $router = new StaticRouter(
            routes: [],
        );

        $configurator = (new ApplicationConfigurator())
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: $router,
            );

        $kernel = $configurator->build();

        self::assertSame($router, $kernel->router);
    }

    public function testBuildPropagatesExplicitConfigToKernel(): void
    {
        $config = new Config();
        $configurator = (new ApplicationConfigurator(
            config: $config,
        ))
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $kernel = $configurator->build();

        self::assertSame($config, $kernel->config);
    }

    public function testBuildReusesProvidedContainer(): void
    {
        $container = new Container();
        $configurator = (new ApplicationConfigurator(
            container: $container,
        ))
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $kernel = $configurator->build();

        self::assertSame($container, $kernel->container);
    }

    public function testBuildCreatesContainerWhenNoneProvided(): void
    {
        $configurator = (new ApplicationConfigurator())
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $kernel = $configurator->build();

        self::assertInstanceOf(Container::class, $kernel->container);
    }

    public function testBuildBindsContainerToItself(): void
    {
        $container = new Container();
        $configurator = (new ApplicationConfigurator(
            container: $container,
        ))
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $configurator->build();

        self::assertSame($container, $container->resolve(ContainerInterface::class));
    }

    public function testBuildRegistersUrlInstanceWhenProvided(): void
    {
        $url = new Url(
            base: 'https://custom.test/',
        );

        $configurator = $this->makeMinimalConfigurator()
            ->withUrl(
                url: $url,
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertSame($url, $container->resolve(UrlInterface::class));
    }

    public function testBuildRegistersLazyUrlBasedOnAppUrlWhenNoneProvided(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;
        $url = $container->resolve(UrlInterface::class);

        self::assertInstanceOf(Url::class, $url);
        self::assertSame('https://minimal.test/', $url->base);
    }

    public function testBuildRegistersEventsManagerInstanceWhenProvided(): void
    {
        $eventsManager = new EventsManager(
            container: new Container(),
        );

        $configurator = $this->makeMinimalConfigurator()
            ->withEventsManager(
                eventsManager: $eventsManager,
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertSame($eventsManager, $container->resolve(EventsManagerInterface::class));
    }

    public function testBuildAttachesMiddlewareToKernel(): void
    {
        $configurator = $this->makeMinimalConfigurator();
        $middleware = new RecordingMiddleware();

        $configurator->withMiddleware(
            middleware: $middleware,
        );

        $kernel = $configurator->build();

        self::assertCount(1, $kernel->middleware);
        self::assertSame($middleware, ($kernel->middleware[0])());
    }

    public function testBuildAttachesMultipleMiddlewareToKernelInOrder(): void
    {
        $first = new RecordingMiddleware();
        $second = new RecordingMiddleware();

        $configurator = $this->makeMinimalConfigurator()
            ->withMiddleware(
                middleware: $first,
            )
            ->withMiddleware(
                middleware: $second,
            );

        $kernel = $configurator->build();

        self::assertCount(2, $kernel->middleware);
        self::assertSame($first, ($kernel->middleware[0])());
        self::assertSame($second, ($kernel->middleware[1])());
    }

    public function testBuildSkipsMiddlewareAttachmentWhenNoneRegistered(): void
    {
        $configurator = $this->makeMinimalConfigurator();
        $kernel = $configurator->build();

        self::assertSame([], $kernel->middleware);
    }

    public function testBuildAttachesExceptionHandlersToKernel(): void
    {
        $configurator = $this->makeMinimalConfigurator();
        $handler = self::makeErrorHandler();

        $configurator->withExceptionHandler(
            exceptionClass: \RuntimeException::class,
            handler: $handler,
        );

        $kernel = $configurator->build();

        self::assertArrayHasKey(\RuntimeException::class, $kernel->exceptionHandlers);
        self::assertCount(1, $kernel->exceptionHandlers[\RuntimeException::class]);
        self::assertSame($handler, ($kernel->exceptionHandlers[\RuntimeException::class][0])());
    }

    public function testBuildAttachesDefaultExceptionHandlersToKernel(): void
    {
        $configurator = $this->makeMinimalConfigurator();
        $handler = self::makeErrorHandler();

        $configurator->withDefaultExceptionHandler(
            handler: $handler,
        );

        $kernel = $configurator->build();

        self::assertCount(1, $kernel->defaultExceptionHandlers);
        self::assertSame($handler, ($kernel->defaultExceptionHandlers[0])());
    }

    public function testBuildPrependsDebugHandlerToDefaultExceptionHandlersWhenEnabled(): void
    {
        $configurator = $this->makeMinimalConfigurator();
        $userHandler = self::makeErrorHandler();

        $configurator
            ->withDebugHandler(
                registerPhpErrorHandler: false,
            )
            ->withDefaultExceptionHandler(
                handler: $userHandler,
            );

        $kernel = $configurator->build();

        self::assertCount(2, $kernel->defaultExceptionHandlers);
        self::assertSame($userHandler, ($kernel->defaultExceptionHandlers[1])());
    }

    public function testBuildSkipsDebugHandlerWhenDisabled(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        $configurator->withDefaultExceptionHandler(
            handler: self::makeErrorHandler(),
        );

        $kernel = $configurator->build();

        self::assertCount(1, $kernel->defaultExceptionHandlers);
    }

    public function testBuildInvokesServiceFileClosure(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withServiceFile(
                file: self::SERVICE_FILE,
            );

        $configurator->build();

        self::assertCount(1, ServiceMarker::$invocations);
        self::assertSame(Container::class, ServiceMarker::$invocations[0]);
    }

    public function testBuildInvokesEachServiceFileClosureInOrder(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withServiceFile(
                file: self::SERVICE_FILE,
            )
            ->withServiceFile(
                file: self::SERVICE_FILE,
            );

        $configurator->build();

        self::assertCount(2, ServiceMarker::$invocations);
    }

    public function testBuildIgnoresServiceFileReturningNonClosure(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withServiceFile(
                file: self::SERVICE_NON_CLOSURE_FILE,
            );

        $configurator->build();

        self::assertSame([], ServiceMarker::$invocations);
    }

    public function testBuildResolvesDefaultEmitterFromContainerWhenNoneProvided(): void
    {
        $configurator = (new ApplicationConfigurator())
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $kernel = $configurator->build();

        self::assertInstanceOf(ResponseEmitterInterface::class, $kernel->emitter);
    }

    public function testBuildResolvesDefaultDispatcherFromContainerWhenNoneProvided(): void
    {
        $configurator = (new ApplicationConfigurator())
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withRouter(
                router: new StaticRouter(
                    routes: [],
                ),
            );

        $kernel = $configurator->build();

        self::assertInstanceOf(DispatcherInterface::class, $kernel->dispatcher);
    }

    public function testBuildResolvesDefaultStaticRouterWhenNoRouterOrDirectoryProvided(): void
    {
        $configurator = (new ApplicationConfigurator())
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            );

        $kernel = $configurator->build();

        self::assertInstanceOf(RouterInterface::class, $kernel->router);
    }

    public function testBuildResolvesDynamicRouterWhenDefaultRouterDirectoryProvided(): void
    {
        $configurator = (new ApplicationConfigurator())
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withDefaultRouter(
                directory: '/path/to/controllers',
            );

        $kernel = $configurator->build();

        self::assertInstanceOf(DynamicRouter::class, $kernel->router);
    }

    public function testBuildPropagatesDefaultRouterDirectoryToDiscoverer(): void
    {
        $configurator = (new ApplicationConfigurator(
            container: new Container(),
            config: new Config(),
        ))
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withDefaultRouter(
                directory: '/path/to/controllers',
            );

        $kernel = $configurator->build();

        /** @var DynamicRouter $router */
        $router = $kernel->router;

        /** @var RouteDiscoverer $discoverer */
        $discoverer = $router->discoverer;

        self::assertInstanceOf(RouteDiscoverer::class, $router->discoverer);
        self::assertSame('/path/to/controllers', $discoverer->directory);
    }

    public function testBuildPropagatesDefaultRouterBaseNamespaceToDiscoverer(): void
    {
        $configurator = (new ApplicationConfigurator(
            container: new Container(),
            config: new Config(),
        ))
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withDefaultRouter(
                directory: '/path/to/controllers',
                baseNamespace: '\App\Custom\\',
            );

        $kernel = $configurator->build();

        /** @var DynamicRouter $router */
        $router = $kernel->router;

        /** @var RouteDiscoverer $discoverer */
        $discoverer = $router->discoverer;

        self::assertSame('\App\Custom\\', $discoverer->baseNamespace);
    }

    public function testBuildPropagatesDefaultRouterStrictModeToDiscoverer(): void
    {
        $configurator = (new ApplicationConfigurator(
            container: new Container(),
            config: new Config(),
        ))
            ->withEmitter(
                emitter: new StubResponseEmitter(),
            )
            ->withDispatcher(
                dispatcher: new StubDispatcher(
                    result: new Response(),
                ),
            )
            ->withDefaultRouter(
                directory: '/path/to/controllers',
                strictMode: false,
            );

        $kernel = $configurator->build();

        /** @var DynamicRouter $router */
        $router = $kernel->router;

        /** @var RouteDiscoverer $discoverer */
        $discoverer = $router->discoverer;

        self::assertFalse($discoverer->strictMode);
    }

    public function testBuildRegistersViewRenderFromUserSuppliedLumiConfigurator(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        /** @var Container $container */
        $container = $configurator->container;

        $configurator->withLumi(
            lumiConfigurator: new LumiConfigurator(
                container: $container,
            ),
        );

        $configurator->build();

        self::assertTrue($container->isBound(ViewRenderInterface::class));
        self::assertInstanceOf(
            ViewRenderInterface::class,
            $container->resolve(ViewRenderInterface::class),
        );
    }

    public function testBuildRegistersViewRenderFromDefaultLumi(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withDefaultLumi();

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertTrue($container->isBound(ViewRenderInterface::class));
        self::assertInstanceOf(
            ViewRenderInterface::class,
            $container->resolve(ViewRenderInterface::class),
        );
    }

    public function testBuildInvokesCustomizerOnDefaultLumi(): void
    {
        $called = false;

        $configurator = $this->makeMinimalConfigurator()
            ->withDefaultLumi(
                customizer: static function (LumiConfiguratorInterface $lumi) use (&$called): void {
                    $called = true;
                },
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        $container->resolve(ViewRenderInterface::class);

        self::assertTrue($called);
    }

    public function testBuildDoesNotRegisterViewRenderWhenLumiNotConfigured(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertFalse($container->isBound(ViewRenderInterface::class));
    }

    public function testBuildRegistersConnectionManagerFromUserSuppliedInstance(): void
    {
        $manager = new StubConnectionManager();

        $configurator = $this->makeMinimalConfigurator()
            ->withConnectionManager(
                connectionManager: $manager,
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertTrue($container->isBound(ConnectionManagerInterface::class));
        self::assertSame(
            $manager,
            $container->resolve(ConnectionManagerInterface::class),
        );
    }

    public function testBuildRegistersConnectionManagerFromDefault(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withDefaultConnectionManager();

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertTrue($container->isBound(ConnectionManagerInterface::class));
        self::assertInstanceOf(
            ConnectionManagerInterface::class,
            $container->resolve(ConnectionManagerInterface::class),
        );
    }

    public function testBuildInvokesCustomizerOnDefaultConnectionManager(): void
    {
        $called = false;

        $configurator = $this->makeMinimalConfigurator()
            ->withDefaultConnectionManager(
                customizer: static function (ConnectionManagerInterface $manager) use (&$called): void {
                    $called = true;
                },
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        $container->resolve(ConnectionManagerInterface::class);

        self::assertTrue($called);
    }

    public function testBuildDoesNotRegisterConnectionManagerWhenNotConfigured(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertFalse($container->isBound(ConnectionManagerInterface::class));
    }

    public function testBuildRegistersStorageFromUserSuppliedInstance(): void
    {
        $storage = new StubStorage();

        $configurator = $this->makeMinimalConfigurator()
            ->withStorage(
                storage: $storage,
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertTrue($container->isBound(StorageInterface::class));
        self::assertSame(
            $storage,
            $container->resolve(StorageInterface::class),
        );
    }

    public function testBuildRegistersStorageFromDefault(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withDefaultStorage();

        /** @var Container $container */
        $container = $configurator->container;

        $container->singleton(
            new LocalStorageConfig(
                root: \sys_get_temp_dir(),
                allowCaseInsensitiveFilesystem: true,
            ),
        );

        $configurator->build();

        self::assertTrue($container->isBound(StorageInterface::class));
        self::assertInstanceOf(
            LocalStorage::class,
            $container->resolve(StorageInterface::class),
        );
    }

    public function testBuildDoesNotRegisterStorageWhenNotConfigured(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertFalse($container->isBound(StorageInterface::class));
    }

    public function testBuildRegistersMailManagerFromUserSuppliedInstance(): void
    {
        $mailManager = new MailManager(
            transport: new RecordingMailTransport(),
        );

        $configurator = $this->makeMinimalConfigurator()
            ->withMailManager(
                mailManager: $mailManager,
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertTrue($container->isBound(MailManagerInterface::class));
        self::assertSame(
            $mailManager,
            $container->resolve(MailManagerInterface::class),
        );
    }

    public function testBuildRegistersMailManagerFromDefault(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withDefaultMailManager();

        /** @var Container $container */
        $container = $configurator->container;

        $transportConfig = new FileMailTransportConfig(
            directory: \sys_get_temp_dir(),
        );

        $container->singleton(
            new MailManagerConfig(
                transport: $transportConfig,
            ),
        );

        $container->singleton(
            new FileMailTransport(
                config: $transportConfig,
            ),
        );

        $configurator->build();

        self::assertTrue($container->isBound(MailManagerInterface::class));
        self::assertInstanceOf(
            MailManager::class,
            $container->resolve(MailManagerInterface::class),
        );
    }

    public function testBuildRegistersMailManagerWithCustomizer(): void
    {
        $customizerCalls = 0;

        $configurator = $this->makeMinimalConfigurator()
            ->withDefaultMailManager(
                customizer: static function (MailManagerConfiguratorInterface $mailConfigurator) use (&$customizerCalls): void {
                    $customizerCalls++;
                },
            );

        /** @var Container $container */
        $container = $configurator->container;

        $transportConfig = new FileMailTransportConfig(
            directory: \sys_get_temp_dir(),
        );

        $container->singleton(
            new MailManagerConfig(
                transport: $transportConfig,
            ),
        );

        $container->singleton(
            new FileMailTransport(
                config: $transportConfig,
            ),
        );

        $configurator->build();

        $container->resolve(MailManagerInterface::class);

        self::assertSame(1, $customizerCalls);
    }

    public function testBuildDoesNotRegisterMailManagerWhenNotConfigured(): void
    {
        $configurator = $this->makeMinimalConfigurator();

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        self::assertFalse($container->isBound(MailManagerInterface::class));
    }

    public function testWithRouteFileAppendsToRouteFiles(): void
    {
        $configurator = new ApplicationConfigurator();

        $configurator->withRouteFile(
            file: '/routes/web.php',
        );

        $configurator->withRouteFile(
            file: '/routes/api.php',
        );

        self::assertSame(
            [
                '/routes/web.php',
                '/routes/api.php',
            ],
            $configurator->routeFiles,
        );
    }

    public function testWithRouteFileReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withRouteFile(
            file: '/routes/web.php',
        );

        self::assertSame($configurator, $result);
    }

    public function testWithoutRouteFilesClearsRouteFiles(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withRouteFile(
            file: '/routes/web.php',
        );

        $configurator->withoutRouteFiles();

        self::assertSame(
            [],
            $configurator->routeFiles,
        );
    }

    public function testWithoutRouteFilesReturnsFluentSelf(): void
    {
        $configurator = new ApplicationConfigurator();
        $result = $configurator->withoutRouteFiles();

        self::assertSame($configurator, $result);
    }

    public function testWithRouterClearsRouteFiles(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withRouteFile(
            file: '/routes/web.php',
        );

        $configurator->withRouter(
            router: new StaticRouter(
                routes: [],
            ),
        );

        self::assertSame(
            [],
            $configurator->routeFiles,
        );
    }

    public function testWithDefaultRouterClearsRouteFiles(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withRouteFile(
            file: '/routes/web.php',
        );

        $configurator->withDefaultRouter(
            directory: '/path/to/controllers',
        );

        self::assertSame(
            [],
            $configurator->routeFiles,
        );
    }

    public function testBuildProducesStaticRouterFromRouteFiles(): void
    {
        $configurator = $this->makeMinimalConfigurator()
            ->withRouteFile(
                file: __DIR__ . '/../../Fixture/Router/Builder/RouteFiles/valid.php',
            );

        $configurator->build();

        /** @var Container $container */
        $container = $configurator->container;

        $router = $container->resolve(RouterInterface::class);

        self::assertInstanceOf(StaticRouter::class, $router);
        self::assertCount(2, $router->routes);
        self::assertSame('/', $router->routes[0]->path);
        self::assertSame('/about', $router->routes[1]->path);
    }

    public function testWithRouteFileClearsExplicitRouter(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withRouter(
            router: new StaticRouter(
                routes: [],
            ),
        );

        $configurator->withRouteFile(
            file: '/routes/web.php',
        );

        self::assertNull($configurator->router);
    }

    public function testWithRouteFileClearsDefaultRouterFields(): void
    {
        $configurator = new ApplicationConfigurator();
        $configurator->withDefaultRouter(
            directory: '/path/to/controllers',
        );

        $configurator->withRouteFile(
            file: '/routes/web.php',
        );

        self::assertNull($configurator->defaultRouterDirectory);
        self::assertNull($configurator->defaultRouterBaseNamespace);
    }

    private static function makeErrorHandler(): ErrorHandlerInterface
    {
        return new class () implements ErrorHandlerInterface {
            public function handle(
                RequestInterface $request,
                ResponseInterface $response,
                \Throwable $exception,
            ): ResponseInterface {
                return $response;
            }
        };
    }
}
