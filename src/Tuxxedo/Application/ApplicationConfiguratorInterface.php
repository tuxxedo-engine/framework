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

use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Router\RouterInterface;

interface ApplicationConfiguratorInterface extends AbstractConfiguratorInterface
{
    public string $appName {
        get;
    }

    public string $appVersion {
        get;
    }

    public Environment $appEnvironment {
        get;
    }

    public string $appUrl {
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

    public ?RouterInterface $router {
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

    public function withAppName(
        string $name,
    ): self;

    public function withAppVersion(
        string $version,
    ): self;

    public function withAppEnvironment(
        Environment $environment,
    ): self;

    public function withAppUrl(
        string $url,
    ): self;

    public function withDefaultRouter(
        string $directory,
        string $baseNamespace = '\App\Controllers\\',
        bool $strictMode = true,
    ): self;

    public function withRouter(
        RouterInterface $router,
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

    public function withRouteFile(
        string $file,
    ): self;

    public function withoutRouteFiles(): self;

    public function build(): KernelInterface;
}
