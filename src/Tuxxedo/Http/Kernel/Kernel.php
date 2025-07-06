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
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewarePipeline;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseExceptionInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\RouterInterface;
use Tuxxedo\Services\ServiceProviderInterface;

class Kernel
{
    public readonly Container $container;

    /**
     * @var array<(\Closure(): MiddlewareInterface)>
     */
    private array $middleware = [];

    /**
     * @var array<class-string<\Throwable>, array<\Closure(): ErrorHandlerInterface>>
     */
    private array $exceptions = [];

    private ResponseEmitterInterface $emitter;
    private RouterInterface $router;

    /**
     * @var array<(\Closure(): ErrorHandlerInterface)>
     */
    private array $defaultExceptionHandlers = [];

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

        $this->emitter = new ResponseEmitter();
    }

    public static function createFromDirectory(
        string $directory,
    ): static {
        $config = Config::createFromDirectory($directory . '/config');

        return new static(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', Profile::class),
            config: $config,
        );
    }

    /**
     * @param ServiceProviderInterface|(\Closure(): ServiceProviderInterface) $provider
     * @return $this
     */
    public function serviceProvider(
        ServiceProviderInterface|\Closure $provider,
    ): static {
        if ($provider instanceof \Closure) {
            $provider = $provider();
        }

        $provider->load($this->container);

        return $this;
    }

    public function emitter(
        ResponseEmitter $emitter,
    ): static {
        $this->emitter = $emitter;

        return $this;
    }

    public function router(
        RouterInterface $router,
    ): static {
        $this->router = $router;

        return $this;
    }

    /**
     * @param (\Closure(): MiddlewareInterface)|MiddlewareInterface $middleware
     * @return $this
     */
    public function middleware(
        \Closure|MiddlewareInterface $middleware,
    ): static {
        if (!$middleware instanceof \Closure) {
            $middleware = static fn (): MiddlewareInterface => $middleware;
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

        if ($e instanceof ResponseExceptionInterface) {
            $response = $e->send();
        } else {
            $response = new Response();
        }

        foreach ($handlers as $handler) {
            $response = ($handler())->handle($request, $response, $e);
        }

        $this->emitter->emit($response);
    }

    public function run(
        ?RequestInterface $request = null,
    ): void {
        $request ??= $this->container->resolve(Request::class);

        try {
            if (!isset($this->router)) {
                throw HttpException::fromInternalServerError();
            }

            $route = $this->router->findByRequest(
                request: $request,
            );

            if ($route === null) {
                throw HttpException::fromNotFound();
            }

            $this->emitter->emit(
                response: (new MiddlewarePipeline(
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
