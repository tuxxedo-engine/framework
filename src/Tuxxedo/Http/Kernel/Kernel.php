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

use Tuxxedo\Config\Config;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Handler\RequestHandlerInterface;
use Tuxxedo\Http\Request\Handler\RequestHandlerPipeline;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\RouterInterface;

class Kernel
{
    public readonly Container $container;

    /**
     * @var array<(\Closure(): RequestHandlerInterface)>
     */
    private array $middleware = [];

    /**
     * @var array<class-string<\Throwable>, array<\Closure(): ErrorHandlerInterface>>
     */
    private array $exceptions = [];

    /**
     * @var array<(\Closure(): ErrorHandlerInterface)>
     */
    private array $defaultExceptionHandlers = [];

    // @todo Redesign part of this so services can be more centralized and registered prior
    final public function __construct(
        public readonly string $appName = '',
        public readonly string $appVersion = '',
        public readonly Profile $appProfile = Profile::RELEASE,
        ?Container $container = null,
        ?Config $config = null,
    ) {
        $this->container = $container ?? new Container();

        $this->container->persistent($this);
        $this->container->persistent($this->container);
        $this->container->persistent($config ?? new Config());

        // @todo Implement loading of app/services.php into $this->container, providers?

        // @todo Register error handling, depending on what the turn out from the $this->appName
        //       verdict above, this may need similar treatment. $this->appProfile will be the main thing
        //       that affects the error handling. This needs to likely include a set_error_handler() call.

        $this->container->persistent(new ResponseEmitter());

        // @todo Register the Router

        // @todo Register the ResponseRenderer (Middleware?)

        // @todo Once the router is registered, look into the routes and where it retrieve its
        //       internal database, which could for example be static, app/routes.php,
        //       static attributes (via precompiled file) or dynamic attributes via reflection

        // @todo Register error middleware in debug profile?
    }

    public static function createFromDirectory(
        string $directory,
    ): Kernel {
        $config = Config::createFromDirectory($directory . '/config');

        return new Kernel(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', Profile::class),
            config: $config,
        );
    }

    /**
     * @param (\Closure(): RequestHandlerInterface)|RequestHandlerInterface $middleware
     * @return $this
     */
    public function middleware(
        \Closure|RequestHandlerInterface $middleware,
    ): static {
        if (!$middleware instanceof \Closure) {
            $middleware = static fn (): RequestHandlerInterface => $middleware;
        }

        $this->middleware[] = $middleware;

        return $this;
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
            $handler = static fn (): ErrorHandlerInterface => $handler;
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
            $handler = static fn (): ErrorHandlerInterface => $handler;
        }

        $this->defaultExceptionHandlers[] = $handler;

        return $this;
    }

    /**
     * @throws \Throwable
     */
    protected function handleException(
        RequestInterface $request,
        \Throwable $e,
    ): void {
        $handlers = [];

        if (\array_key_exists($e::class, $this->exceptions)) {
            $handlers = $this->exceptions[$e::class];
        }

        $handlers = \array_merge($handlers, $this->defaultExceptionHandlers);

        if (\sizeof($handlers) === 0) {
            throw $e;
        }

        foreach ($handlers as $handler) {
            ($handler())->handle($request, $e);
        }
    }

    public function run(
        ?RequestInterface $request = null,
    ): void {
        $request ??= $this->container->resolve(Request::class);

        try {
            $route = $this->container->resolve(RouterInterface::class)->findByRequest(
                request: $request,
            );

            if ($route === null) {
                throw HttpException::fromNotFound();
            }

            $this->container->resolve(ResponseEmitterInterface::class)->emit(
                response: (new RequestHandlerPipeline(
                    container: $this->container,
                    resolver: static function (Container $container) use ($route, $request): ResponseInterface {
                        $callback = [
                            $container->resolve($route->controller),
                            $route->action,
                        ];

                        if (!\is_callable($callback)) {
                            throw HttpException::fromInternalServerError();
                        }

                        $response = \call_user_func($callback, $request);

                        if (!$response instanceof ResponseInterface) {
                            throw HttpException::fromInternalServerError();
                        }

                        return $response;
                    },
                    middleware: \array_reverse(
                        \array_merge(
                            $this->middleware,
                            $route->middleware,
                        ),
                    ),
                ))->run($request),
            );
        } catch (\Throwable $e) {
            $this->handleException($request, $e);
        }
    }
}
