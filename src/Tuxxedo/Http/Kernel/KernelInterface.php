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

namespace Tuxxedo\Http\Kernel;

use Tuxxedo\Application\Profile;
use Tuxxedo\Application\ServiceProviderInterface;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Discovery\DiscoveryChannelInterface;
use Tuxxedo\Discovery\DiscoveryType;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Router\RouterInterface;

interface KernelInterface
{
    public ConfigInterface $config {
        get;
    }

    public ContainerInterface $container {
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
    public array $exceptions {
        get;
    }

    public ResponseEmitterInterface $emitter {
        get;
    }

    public RouterInterface $router {
        get;
    }

    /**
     * @var array<(\Closure(): ErrorHandlerInterface)>
     */
    public array $defaultExceptionHandlers {
        get;
    }

    public string $appName {
        get;
    }

    public string $appVersion {
        get;
    }

    public Profile $appProfile {
        get;
    }

    /**
     * @param ServiceProviderInterface|(\Closure(): ServiceProviderInterface) $provider
     */
    public function serviceProvider(
        ServiceProviderInterface|\Closure $provider,
    ): static;

    public function emitter(
        ResponseEmitterInterface $emitter,
    ): static;

    public function router(
        RouterInterface $router,
    ): static;

    public function discover(
        DiscoveryChannelInterface $channel,
        ?DiscoveryType $discoveryType = null,
    ): static;

    /**
     * @param (\Closure(): MiddlewareInterface)|MiddlewareInterface $middleware
     */
    public function middleware(
        \Closure|MiddlewareInterface $middleware,
    ): static;

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param (\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface $handler
     */
    public function whenException(
        string $exceptionClass,
        \Closure|ErrorHandlerInterface $handler,
    ): static;

    /**
     * @param (\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface $handler
     */
    public function defaultExceptionHandler(
        \Closure|ErrorHandlerInterface $handler,
    ): static;

    public function run(
        ?RequestInterface $request = null,
    ): void;
}
