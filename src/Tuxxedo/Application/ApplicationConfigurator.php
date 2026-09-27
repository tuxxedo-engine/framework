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
use Tuxxedo\Debug\Config\DebugConfigInterface;
use Tuxxedo\Debug\DebugErrorHandler;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Event\EventsManager;
use Tuxxedo\Event\EventsManagerInterface;
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
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Router\RouterInterface;
use Tuxxedo\Router\StaticRouter;

class ApplicationConfigurator extends AbstractConfigurator implements ApplicationConfiguratorInterface
{
    public private(set) ?string $defaultRouterDirectory = null;
    public private(set) ?string $defaultRouterBaseNamespace = null;
    public private(set) bool $defaultRouterStrictMode = true;
    public private(set) ?RouterInterface $router = null;

    public private(set) array $middleware = [];
    public private(set) array $exceptionHandlers = [];
    public private(set) array $defaultExceptionHandlers = [];

    /**
     * @var list<string>
     */
    public private(set) array $routeFiles = [];

    final public function __construct(
        public private(set) string $appName = '',
        public private(set) string $appVersion = '',
        public private(set) Environment $appEnvironment = Environment::PRODUCTION,
        public private(set) string $appUrl = '',
        ?ConfigInterface $config = null,
        ?ContainerInterface $container = null,
    ) {
        parent::__construct(
            config: $config,
            container: $container,
        );
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
            appEnvironment: $appConfig->environment,
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
            appEnvironment: $appConfig->environment,
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

    public function withAppEnvironment(
        Environment $environment,
    ): self {
        $this->appEnvironment = $environment;

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

    public function build(): KernelInterface
    {
        $container = $this->container ?? new Container();

        $container->singleton($container);
        $container->singleton($this->config ?? Config::class);

        if (!$container->isBound(ResponseEmitterInterface::class)) {
            $container->singleton(ResponseEmitter::class);
        }

        if (!$container->isBound(DispatcherInterface::class)) {
            $container->singleton(Dispatcher::class);
        }

        if (!$container->isBound(EventsManagerInterface::class)) {
            $container->singleton(EventsManager::class);
        }

        if (!$container->isBound(UrlInterface::class)) {
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

        $this->registerLumi($container);
        $this->registerConnectionManager($container);
        $this->registerStorage($container);
        $this->registerMailManager($container);

        $container->singletonLazy(
            KernelInterface::class,
            fn (ContainerInterface $container): KernelInterface => $container->resolve(
                Kernel::class,
                [
                    'appName' => $this->appName,
                    'appVersion' => $this->appVersion,
                    'appEnvironment' => $this->appEnvironment,
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
            $this->appEnvironment === Environment::DEVELOPMENT &&
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

        $this->loadServiceFiles($container);

        return $kernel;
    }
}
