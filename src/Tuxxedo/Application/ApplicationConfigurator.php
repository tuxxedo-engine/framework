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

namespace Tuxxedo\Application;

use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Debug\DebugErrorHandler;
use Tuxxedo\Env\Env;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Env\EnvLoaderInterface;
use Tuxxedo\Env\GetEnvLoader;
use Tuxxedo\Http\Kernel\DispatcherInterface;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Router\RouterInterface;

class ApplicationConfigurator implements ApplicationConfiguratorInterface
{
    public private(set) ?ConfigInterface $config = null;
    public private(set) ?ContainerInterface $container = null;
    public private(set) ?string $defaultRouterDirectory = null;
    public private(set) ?string $defaultRouterBaseNamespace = null;
    public private(set) bool $defaultRouterStrictMode = true;
    public private(set) bool $useDebugHandler = false;
    public private(set) bool $registerPhpErrorHandler = true;
    public private(set) ?RouterInterface $router = null;
    public private(set) ?ResponseEmitterInterface $emitter = null;
    public private(set) ?DispatcherInterface $dispatcher = null;
    public private(set) ?EnvLoaderInterface $envLoader = null;

    public private(set) array $middleware = [];
    public private(set) array $exceptionHandlers = [];
    public private(set) array $defaultExceptionHandlers = [];
    public private(set) array $serviceFiles = [];

    final public function __construct(
        public private(set) string $appName = '',
        public private(set) string $appVersion = '',
        public private(set) Profile $appProfile = Profile::RELEASE,
    ) {
    }

    public static function createFromConfigFile(
        string $file,
    ): static {
        $config = Config::createFromFile($file);

        /** @var static */
        return (new static(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', Profile::class),
        ))
            ->withConfig($config);
    }

    public static function createFromConfigDirectory(
        string $directory,
    ): static {
        $config = Config::createFromDirectory($directory);

        /** @var static */
        return (new static(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', Profile::class),
        ))
            ->withConfig($config);
    }

    public function withoutConfig(): self
    {
        $this->config = null;

        return $this;
    }

    public function withConfig(
        ConfigInterface $config,
    ): self {
        $this->config = $config;

        return $this;
    }

    public function withoutContainer(): self
    {
        $this->container = null;

        return $this;
    }

    public function withContainer(
        ContainerInterface $container,
    ): self {
        $this->container = $container;

        return $this;
    }

    public function withDefaultRouter(
        string $directory,
        string $baseNamespace = '\App\Controllers\\',
        bool $strictMode = true,
    ): self {
        $this->router = null;
        $this->defaultRouterDirectory = $directory;
        $this->defaultRouterBaseNamespace = $baseNamespace;
        $this->defaultRouterStrictMode = $strictMode;

        return $this;
    }

    public function withRouter(
        RouterInterface $router,
    ): self {
        $this->router = $router;
        $this->defaultRouterDirectory = null;
        $this->defaultRouterBaseNamespace = null;

        return $this;
    }

    public function withDefaultEmitter(): self
    {
        $this->emitter = null;

        return $this;
    }

    public function withEmitter(
        ResponseEmitterInterface $emitter,
    ): self {
        $this->emitter = $emitter;

        return $this;
    }

    public function withDefaultDispatcher(): self
    {
        $this->dispatcher = null;

        return $this;
    }

    public function withDispatcher(
        DispatcherInterface $dispatcher,
    ): self {
        $this->dispatcher = $dispatcher;

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

    public function withoutDebugHandler(): self
    {
        $this->useDebugHandler = false;

        return $this;
    }

    public function withDebugHandler(
        bool $registerPhpErrorHandler = true,
    ): self {
        $this->useDebugHandler = true;
        $this->registerPhpErrorHandler = $registerPhpErrorHandler;

        return $this;
    }

    public function withoutEnvLoader(): self
    {
        $this->envLoader = null;

        return $this;
    }

    public function withEnvLoader(
        EnvLoaderInterface $loader,
    ): self {
        $this->envLoader = $loader;

        return $this;
    }

    public function withDefaultEnvLoader(): self
    {
        $this->envLoader = new GetEnvLoader();

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
        $kernel = new Kernel(
            appName: $this->appName,
            appVersion: $this->appVersion,
            appProfile: $this->appProfile,
            container: $this->container,
            config: $this->config,
        );

        if ($this->router !== null) {
            $kernel->router(
                router: $this->router,
            );
        } elseif (
            $this->defaultRouterDirectory !== null &&
            $this->defaultRouterBaseNamespace !== null
        ) {
            $kernel->router(
                router: DynamicRouter::createFromDirectory(
                    container: $kernel->container,
                    directory: $this->defaultRouterDirectory,
                    baseNamespace: $this->defaultRouterBaseNamespace,
                    strictMode: $this->defaultRouterStrictMode,
                ),
            );
        }

        if (
            $this->emitter !== null &&
            $this->emitter !== $kernel->emitter
        ) {
            $kernel->emitter($this->emitter);
        }

        if (
            $this->dispatcher !== null &&
            $this->dispatcher !== $kernel->dispatcher
        ) {
            $kernel->dispatcher($this->dispatcher);
        }

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

        if ($this->useDebugHandler) {
            $kernel->defaultExceptionHandler(
                handler: fn (): ErrorHandlerInterface => new DebugErrorHandler(
                    registerPhpErrorHandler: $this->registerPhpErrorHandler,
                ),
            );
        }

        if (\sizeof($this->defaultExceptionHandlers) > 0) {
            foreach ($this->defaultExceptionHandlers as $handler) {
                $kernel->defaultExceptionHandler($handler);
            }
        }

        if ($this->envLoader !== null) {
            $kernel->container->persistentLazy(
                EnvInterface::class,
                fn (): EnvInterface => new Env(
                    loader: $this->envLoader,
                ),
            );
        }

        if (\sizeof($this->serviceFiles) > 0) {
            foreach ($this->serviceFiles as $serviceFile) {
                $kernel->serviceProvider(
                    provider: new FileServiceProvider($serviceFile),
                );
            }
        }

        return $kernel;
    }
}
