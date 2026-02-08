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

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Kernel\DispatcherInterface;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Router\RouterInterface;

interface ApplicationConfiguratorInterface
{
    public string $appName {
        get;
    }

    public string $appVersion {
        get;
    }

    public Profile $appProfile {
        get;
    }

    public ?ConfigInterface $config {
        get;
    }

    public ?ContainerInterface $container {
        get;
    }

    public ?string $defaultRouterDirectory {
        get;
    }

    public ?string $defaultRouterBaseNamespace {
        get;
    }

    public bool $defaultRouterStrictMode {
        get;
    }

    public bool $useDebugHandler {
        get;
    }

    public bool $registerPhpErrorHandler {
        get;
    }

    public ?RouterInterface $router {
        get;
    }

    public ?ResponseEmitterInterface $emitter {
        get;
    }

    public ?DispatcherInterface $dispatcher {
        get;
    }

    /**
     * @var array<(\Closure(): MiddlewareInterface)>
     */
    public array $middleware {
        get;
    }

    /**
     * @var array<class-string<\Throwable>, array<\Closure(): ErrorHandlerInterface>>
     */
    public array $exceptionHandlers {
        get;
    }

    /**
     * @var array<(\Closure(): ErrorHandlerInterface)>
     */
    public array $defaultExceptionHandlers {
        get;
    }

    /**
     * @var string[]
     */
    public array $serviceFiles {
        get;
    }

    public function withAppName(
        string $name,
    ): self;

    public function withAppVersion(
        string $version,
    ): self;

    public function withAppProfile(
        Profile $profile,
    ): self;

    public function withoutConfig(): self;

    public function withConfig(
        ConfigInterface $config,
    ): self;

    public function withoutContainer(): self;

    public function withContainer(
        ContainerInterface $container,
    ): self;

    public function withDefaultRouter(
        string $directory,
        string $baseNamespace = '\App\Controllers\\',
        bool $strictMode = true,
    ): self;

    public function withRouter(
        RouterInterface $router,
    ): self;

    public function withDefaultEmitter(): self;

    public function withEmitter(
        ResponseEmitterInterface $emitter,
    ): self;

    public function withDefaultDispatcher(): self;

    public function withDispatcher(
        DispatcherInterface $dispatcher,
    ): self;

    public function withoutMiddleware(): self;

    /**
     * @param (\Closure(): MiddlewareInterface)|MiddlewareInterface $middleware
     */
    public function withMiddleware(
        \Closure|MiddlewareInterface $middleware,
    ): self;

    public function withoutExceptionHandlers(): self;

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param (\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface $handler
     */
    public function withExceptionHandler(
        string $exceptionClass,
        \Closure|ErrorHandlerInterface $handler,
    ): self;

    public function withoutDefaultExceptionHandlers(): self;

    /**
     * @param (\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface $handler
     */
    public function withDefaultExceptionHandler(
        \Closure|ErrorHandlerInterface $handler,
    ): self;

    public function withoutDebugHandler(): self;

    public function withDebugHandler(
        bool $registerPhpErrorHandler = true,
    ): self;

    public function withoutServiceFiles(): self;

    public function withServiceFile(
        string $file,
    ): self;

    public function build(): KernelInterface;
}
