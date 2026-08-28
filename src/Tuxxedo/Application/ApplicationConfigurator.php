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

namespace Tuxxedo\Application;

use Tuxxedo\Application\Config\AppConfigInterface;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigException;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerException;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Config\ConnectionManagerConfigInterface;
use Tuxxedo\Database\ConnectionManager;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Debug\Config\DebugConfigInterface;
use Tuxxedo\Debug\DebugErrorHandler;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Event\EventsManager;
use Tuxxedo\Event\EventsManagerInterface;
use Tuxxedo\File\Storage\Local\Config\LocalStorageConfigInterface;
use Tuxxedo\File\Storage\Local\LocalStorage;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Http\Kernel\Dispatcher;
use Tuxxedo\Http\Kernel\DispatcherInterface;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Mail\MailManagerConfigurator;
use Tuxxedo\Mail\MailManagerConfiguratorInterface;
use Tuxxedo\Mail\MailManagerInterface;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Router\RouterInterface;
use Tuxxedo\Router\StaticRouter;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;
use Tuxxedo\View\Lumi\LumiViewRenderInterface;
use Tuxxedo\View\ViewRenderInterface;

class ApplicationConfigurator implements ApplicationConfiguratorInterface
{
    public private(set) ?string $defaultRouterDirectory = null;
    public private(set) ?string $defaultRouterBaseNamespace = null;
    public private(set) bool $defaultRouterStrictMode = true;
    public private(set) ?RouterInterface $router = null;
    public private(set) ?ResponseEmitterInterface $emitter = null;
    public private(set) ?DispatcherInterface $dispatcher = null;
    public private(set) ?EventsManagerInterface $eventsManager = null;
    public private(set) ?UrlInterface $url = null;
    public private(set) ?LumiConfiguratorInterface $lumiConfigurator = null;
    public private(set) bool $useDefaultLumi = false;
    public private(set) ?\Closure $lumiCustomizer = null;
    public private(set) ?ConnectionManagerInterface $connectionManager = null;
    public private(set) bool $useDefaultConnectionManager = false;
    public private(set) ?\Closure $connectionManagerCustomizer = null;
    public private(set) ?StorageInterface $storage = null;
    public private(set) bool $useDefaultStorage = false;
    public private(set) ?MailManagerInterface $mailManager = null;
    public private(set) bool $useDefaultMailManager = false;
    public private(set) ?\Closure $mailManagerCustomizer = null;

    public private(set) array $middleware = [];
    public private(set) array $exceptionHandlers = [];
    public private(set) array $defaultExceptionHandlers = [];
    public private(set) array $serviceFiles = [];

    /**
     * @var list<string>
     */
    public private(set) array $routeFiles = [];

    final public function __construct(
        public private(set) string $appName = '',
        public private(set) string $appVersion = '',
        public private(set) Profile $appProfile = Profile::RELEASE,
        public private(set) string $appUrl = '',
        public private(set) ?ConfigInterface $config = null,
        public private(set) ?ContainerInterface $container = null,
    ) {
    }

    public static function createFromConfigFile(
        string $file,
        ?ContainerInterface $container = null,
        ?EnvInterface $env = null,
    ): static {
        $container ??= new Container();

        if ($env !== null) {
            $container->singleton($env);
        }

        $config = Config::createFromFile($container, $file);
        $appConfig = self::resolveAppConfig($container);

        return new static(
            appName: $appConfig->name,
            appVersion: $appConfig->version,
            appProfile: $appConfig->profile,
            appUrl: $appConfig->url,
            container: $container,
            config: $config,
        );
    }

    public static function createFromConfigDirectory(
        string $directory,
        ?ContainerInterface $container = null,
        ?EnvInterface $env = null,
    ): static {
        $container ??= new Container();

        if ($env !== null) {
            $container->singleton($env);
        }

        $config = Config::createFromDirectory($container, $directory);
        $appConfig = self::resolveAppConfig($container);

        return new static(
            appName: $appConfig->name,
            appVersion: $appConfig->version,
            appProfile: $appConfig->profile,
            appUrl: $appConfig->url,
            container: $container,
            config: $config,
        );
    }

    private static function resolveAppConfig(
        ContainerInterface $container,
    ): AppConfigInterface {
        try {
            return $container->resolve(AppConfigInterface::class);
        } catch (ContainerException $exception) {
            throw ConfigException::fromMissingAppConfig(
                previous: $exception,
            );
        }
    }

    public function withAppName(
        string $name,
    ): self {
        $this->appName = $name;

        return $this;
    }

    public function withAppVersion(
        string $version,
    ): self {
        $this->appVersion = $version;

        return $this;
    }

    public function withAppProfile(
        Profile $profile,
    ): self {
        $this->appProfile = $profile;

        return $this;
    }

    public function withAppUrl(
        string $url,
    ): self {
        $this->appUrl = $url;

        return $this;
    }

    public function withDefaultRouter(
        string $directory,
        string $baseNamespace = '\App\Controllers\\',
        bool $strictMode = true,
    ): self {
        $this->router = null;
        $this->routeFiles = [];
        $this->defaultRouterDirectory = $directory;
        $this->defaultRouterBaseNamespace = $baseNamespace;
        $this->defaultRouterStrictMode = $strictMode;

        return $this;
    }

    public function withRouter(
        RouterInterface $router,
    ): self {
        $this->router = $router;
        $this->routeFiles = [];
        $this->defaultRouterDirectory = null;
        $this->defaultRouterBaseNamespace = null;

        return $this;
    }

    public function withRouteFile(
        string $file,
    ): self {
        $this->router = null;
        $this->defaultRouterDirectory = null;
        $this->defaultRouterBaseNamespace = null;
        $this->routeFiles[] = $file;

        return $this;
    }

    public function withoutRouteFiles(): self
    {
        $this->routeFiles = [];

        return $this;
    }

    public function withEmitter(
        ResponseEmitterInterface $emitter,
    ): self {
        $this->emitter = $emitter;

        return $this;
    }

    public function withDispatcher(
        DispatcherInterface $dispatcher,
    ): self {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    public function withEventsManager(
        EventsManagerInterface $eventsManager,
    ): self {
        $this->eventsManager = $eventsManager;

        return $this;
    }

    public function withUrl(
        UrlInterface $url,
    ): self {
        $this->url = $url;

        return $this;
    }

    public function withLumi(
        LumiConfiguratorInterface $lumiConfigurator,
    ): self {
        $this->lumiConfigurator = $lumiConfigurator;
        $this->useDefaultLumi = false;
        $this->lumiCustomizer = null;

        return $this;
    }

    /**
     * @param (\Closure(LumiConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultLumi(
        ?\Closure $customizer = null,
    ): self {
        $this->useDefaultLumi = true;
        $this->lumiCustomizer = $customizer;
        $this->lumiConfigurator = null;

        return $this;
    }

    public function withConnectionManager(
        ConnectionManagerInterface $connectionManager,
    ): self {
        $this->connectionManager = $connectionManager;
        $this->useDefaultConnectionManager = false;
        $this->connectionManagerCustomizer = null;

        return $this;
    }

    /**
     * @param (\Closure(ConnectionManagerInterface $manager): mixed)|null $customizer
     */
    public function withDefaultConnectionManager(
        ?\Closure $customizer = null,
    ): self {
        $this->useDefaultConnectionManager = true;
        $this->connectionManagerCustomizer = $customizer;
        $this->connectionManager = null;

        return $this;
    }

    public function withStorage(
        StorageInterface $storage,
    ): self {
        $this->storage = $storage;
        $this->useDefaultStorage = false;

        return $this;
    }

    public function withDefaultStorage(): self
    {
        $this->useDefaultStorage = true;
        $this->storage = null;

        return $this;
    }

    public function withMailManager(
        MailManagerInterface $mailManager,
    ): self {
        $this->mailManager = $mailManager;
        $this->useDefaultMailManager = false;
        $this->mailManagerCustomizer = null;

        return $this;
    }

    /**
     * @param (\Closure(MailManagerConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultMailManager(
        ?\Closure $customizer = null,
    ): self {
        $this->useDefaultMailManager = true;
        $this->mailManagerCustomizer = $customizer;
        $this->mailManager = null;

        return $this;
    }

    public function withoutMiddleware(): self
    {
        $this->middleware = [];

        return $this;
    }

    public function withMiddleware(
        \Closure|MiddlewareInterface $middleware,
    ): self {
        if (!$middleware instanceof \Closure) {
            $middleware = static fn (): MiddlewareInterface => $middleware;
        }

        $this->middleware[] = $middleware;

        return $this;
    }

    public function withoutExceptionHandlers(): self
    {
        $this->exceptionHandlers = [];

        return $this;
    }

    public function withExceptionHandler(
        string $exceptionClass,
        \Closure|ErrorHandlerInterface $handler,
    ): self {
        if (!$handler instanceof \Closure) {
            $handler = static fn (): ErrorHandlerInterface => $handler;
        }

        $this->exceptionHandlers[$exceptionClass] ??= [];
        $this->exceptionHandlers[$exceptionClass][] = $handler;

        return $this;
    }

    public function withoutDefaultExceptionHandlers(): self
    {
        $this->defaultExceptionHandlers = [];

        return $this;
    }

    public function withDefaultExceptionHandler(
        \Closure|ErrorHandlerInterface $handler,
    ): self {
        if (!$handler instanceof \Closure) {
            $handler = static fn (): ErrorHandlerInterface => $handler;
        }

        $this->defaultExceptionHandlers[] = $handler;

        return $this;
    }

    public function withoutServiceFiles(): self
    {
        $this->serviceFiles = [];

        return $this;
    }

    public function withServiceFile(
        string $file,
    ): self {
        $this->serviceFiles[] = $file;

        return $this;
    }

    public function build(): KernelInterface
    {
        $container = $this->container ?? new Container();

        $container->singleton($container);
        $container->singleton($this->config ?? Config::class);
        $container->singleton($this->emitter ?? ResponseEmitter::class);
        $container->singleton($this->dispatcher ?? Dispatcher::class);
        $container->singleton($this->eventsManager ?? EventsManager::class);

        if ($this->url !== null) {
            $container->singleton($this->url);
        } else {
            $container->singletonLazy(
                UrlInterface::class,
                fn (): UrlInterface => new Url(
                    base: $this->appUrl,
                ),
            );
        }

        if ($this->router !== null) {
            $container->singleton($this->router);
        } elseif ($this->routeFiles !== []) {
            $container->singletonLazy(
                RouterInterface::class,
                fn (ContainerInterface $container): RouterInterface => StaticRouter::createFromRouteFiles(
                    container: $container,
                    files: $this->routeFiles,
                ),
            );
        } elseif (
            $this->defaultRouterDirectory !== null &&
            $this->defaultRouterBaseNamespace !== null
        ) {
            $container->singletonLazy(
                RouterInterface::class,
                fn (ContainerInterface $container): RouterInterface => DynamicRouter::createFromDirectory(
                    container: $container,
                    directory: $this->defaultRouterDirectory,
                    baseNamespace: $this->defaultRouterBaseNamespace,
                    strictMode: $this->defaultRouterStrictMode,
                ),
            );
        } else {
            $container->singletonLazy(
                RouterInterface::class,
                static fn (): RouterInterface => new StaticRouter(
                    routes: [],
                ),
            );
        }

        if ($this->lumiConfigurator !== null) {
            $lumiConfigurator = $this->lumiConfigurator;

            $container->singletonLazy(
                LumiViewRenderInterface::class,
                static fn (): LumiViewRenderInterface => $lumiConfigurator->build(),
            );

            $container->alias(
                ViewRenderInterface::class,
                LumiViewRenderInterface::class,
            );
        } elseif ($this->useDefaultLumi) {
            $customizer = $this->lumiCustomizer;

            $container->singletonLazy(
                LumiViewRenderInterface::class,
                static function (ContainerInterface $container) use ($customizer): LumiViewRenderInterface {
                    $lumi = LumiConfigurator::fromConfig($container);

                    if ($customizer !== null) {
                        $customizer($lumi);
                    }

                    return $lumi->build();
                },
            );

            $container->alias(
                ViewRenderInterface::class,
                LumiViewRenderInterface::class,
            );
        }

        if ($this->connectionManager !== null) {
            $connectionManager = $this->connectionManager;

            $container->singletonLazy(
                ConnectionManagerInterface::class,
                static fn (): ConnectionManagerInterface => $connectionManager,
            );
        } elseif ($this->useDefaultConnectionManager) {
            $customizer = $this->connectionManagerCustomizer;

            $container->singletonLazy(
                ConnectionManagerInterface::class,
                static function (ContainerInterface $container) use ($customizer): ConnectionManagerInterface {
                    $manager = ConnectionManager::createFromConfig(
                        container: $container,
                        config: $container->resolve(ConnectionManagerConfigInterface::class),
                    );

                    if ($customizer !== null) {
                        $customizer($manager);
                    }

                    return $manager;
                },
            );
        }

        if ($this->storage !== null) {
            $storage = $this->storage;

            $container->singletonLazy(
                StorageInterface::class,
                static fn (): StorageInterface => $storage,
            );
        } elseif ($this->useDefaultStorage) {
            $container->singletonLazy(
                StorageInterface::class,
                static fn (ContainerInterface $container): StorageInterface => new LocalStorage(
                    config: $container->resolve(LocalStorageConfigInterface::class),
                ),
            );
        }

        if ($this->mailManager !== null) {
            $mailManager = $this->mailManager;

            $container->singletonLazy(
                MailManagerInterface::class,
                static fn (): MailManagerInterface => $mailManager,
            );
        } elseif ($this->useDefaultMailManager) {
            $customizer = $this->mailManagerCustomizer;

            $container->singletonLazy(
                MailManagerInterface::class,
                static function (ContainerInterface $container) use ($customizer): MailManagerInterface {
                    $configurator = MailManagerConfigurator::fromConfig($container);

                    if ($customizer !== null) {
                        $customizer($configurator);
                    }

                    return $configurator->build();
                },
            );
        }

        $container->singletonLazy(
            KernelInterface::class,
            fn (ContainerInterface $container): KernelInterface => $container->resolve(
                Kernel::class,
                [
                    'appName' => $this->appName,
                    'appVersion' => $this->appVersion,
                    'appProfile' => $this->appProfile,
                    'appUrl' => $this->appUrl,
                ],
            ),
        );

        $kernel = $container->resolve(KernelInterface::class);

        if (\sizeof($this->middleware) > 0) {
            foreach ($this->middleware as $middleware) {
                $kernel->middleware($middleware);
            }
        }

        if (\sizeof($this->exceptionHandlers) > 0) {
            foreach ($this->exceptionHandlers as $exceptionClass => $handlers) {
                foreach ($handlers as $handler) {
                    $kernel->whenException($exceptionClass, $handler);
                }
            }
        }

        if (
            $this->appProfile === Profile::DEBUG &&
            $container->isBound(DebugConfigInterface::class)
        ) {
            $kernel->defaultExceptionHandler(
                handler: fn (): ErrorHandlerInterface => new DebugErrorHandler(
                    container: $container,
                    config: $container->resolve(DebugConfigInterface::class),
                ),
            );
        }

        if (\sizeof($this->defaultExceptionHandlers) > 0) {
            foreach ($this->defaultExceptionHandlers as $handler) {
                $kernel->defaultExceptionHandler($handler);
            }
        }

        if (\sizeof($this->serviceFiles) > 0) {
            foreach ($this->serviceFiles as $serviceFile) {
                $container->callFile($serviceFile);
            }
        }

        return $kernel;
    }
}
