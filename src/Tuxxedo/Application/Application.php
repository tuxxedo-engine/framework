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
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Request\RequestFactory;
use Tuxxedo\Http\Request\RequestInterface;

class Application
{
    public readonly Container $container;

    /**
     * @var array<class-string<\Throwable>, array<\Closure(): ErrorHandlerInterface>>
     */
    private array $exceptions = [];

    /**
     * @var array<(\Closure(): ErrorHandlerInterface)>
     */
    private array $defaultExceptionHandlers = [];

    final public function __construct(
        public readonly string $appName = '',
        public readonly string $appVersion = '',
        public readonly ApplicationState $appState = ApplicationState::RELEASE,
        ?Container $container = null,
        ?Config $config = null,
    ) {
        $this->container = $container ?? new Container();

        $this->container->persistent($this);
        $this->container->persistent($config ?? new Config());

        // @todo Implement loading of app/services.php into $this->container, providers?

        // @todo Register error handling, depending on what the turn out from the $this->appName
        //       verdict above, this may need similar treatment. $this->appState will be the main thing
        //       that affects the error handling. This needs to likely include a set_error_handler() call.

        // @todo Register Request and Response objects here, unless they are passed in directly

        // @todo Register the Router

        // @todo Once the router is registered, look into the routes and where it retrieve its
        //       internal database, which could for example be static, app/routes.php,
        //       static attributes (via precompiled file) or dynamic attributes via reflection

        // @todo Register middleware and create FIFO stack

        // @todo Register error middleware and create FILO stack
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @param (\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface $handler
     */
    public function whenException(
        string $exceptionClass,
        \Closure|ErrorHandlerInterface $handler,
    ): static {
        if (!$handler instanceof \Closure) {
            $handler = static fn(): ErrorHandlerInterface => $handler;
        }

        $this->exceptions[$exceptionClass] ??= [];
        $this->exceptions[$exceptionClass][] = $handler;

        return $this;
    }

    /**
     * @param (\Closure(): ErrorHandlerInterface)|ErrorHandlerInterface $handler
     */
    public function defaultExceptionHandler(
        \Closure|ErrorHandlerInterface $handler,
    ): static {
        if (!$handler instanceof \Closure) {
            $handler = static fn(): ErrorHandlerInterface => $handler;
        }

        $this->defaultExceptionHandlers[] = $handler;

        return $this;
    }

    protected function handleException(
        RequestInterface $request,
        \Throwable $e,
    ): void {
        $handlers = [];

        if (\array_key_exists($e::class, $this->exceptions)) {
            $handlers = $this->exceptions[$e::class];
        }

        $handlers = \array_merge($handlers, $this->defaultExceptionHandlers);

        foreach ($handlers as $handler) {
            ($handler())->handle($request, $e);
        }
    }

    public function run(?RequestInterface $request): void
    {
        $request ??= RequestFactory::createFromEnvironment();

        try {
            // @todo Implement Dispatching logic here by resolving the router, looking up the input
            //       from the current request, error handling and then initializing the controller
            //       code. This needs some extra thought for how the best possible way to avoid
            //       adding boilerplate code for things like. This likely needs to accept some form
            //       of incoming request to dispatch
        } catch (\Throwable $e) {
            $this->handleException($request, $e);
        }
    }
}
