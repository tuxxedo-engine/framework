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
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareNode;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponsableInterface;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseExceptionInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\RouterInterface;

class Kernel implements KernelInterface
{
    public readonly ConfigInterface $config;
    public readonly ContainerInterface $container;

    public private(set) array $middleware = [];

    public private(set) ResponseEmitterInterface $emitter;
    public private(set) RouterInterface $router;

    public private(set) array $exceptionHandlers = [];
    public private(set) array $defaultExceptionHandlers = [];

    final public function __construct(
        public readonly string $appName = '',
        public readonly string $appVersion = '',
        public readonly Profile $appProfile = Profile::RELEASE,
        ?ContainerInterface $container = null,
        ?ConfigInterface $config = null,
    ) {
        $this->config = $config ?? new Config();
        $this->container = $container ?? new Container();

        $this->container->bind($this);
        $this->container->bind($this->config);
        $this->container->bind($this->container);

        $this->emitter = new ResponseEmitter();
    }

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
        ResponseEmitterInterface $emitter,
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

    public function middleware(
        \Closure|MiddlewareInterface $middleware,
    ): static {
        if (!$middleware instanceof \Closure) {
            $middleware = static fn (): MiddlewareInterface => $middleware;
        }

        $this->middleware[] = $middleware;

        return $this;
    }

    public function whenException(
        string $exceptionClass,
        \Closure|ErrorHandlerInterface $handler,
    ): static {
        if (!$handler instanceof \Closure) {
            $handler = static fn (): ErrorHandlerInterface => $handler;
        }

        $this->exceptionHandlers[$exceptionClass] ??= [];
        $this->exceptionHandlers[$exceptionClass][] = $handler;

        return $this;
    }

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

        if (\array_key_exists($e::class, $this->exceptionHandlers)) {
            $handlers = $this->exceptionHandlers[$e::class];
        }

        $handlers = \array_merge($handlers, $this->defaultExceptionHandlers);

        if ($e instanceof ResponseExceptionInterface) {
            $response = $e->send();
        } else {
            $response = HttpException::fromInternalServerError()->send();
        }

        foreach ($handlers as $handler) {
            $response = ($handler())->handle($request, $response, $e);

            if ($response instanceof ResponsableInterface) {
                $response = $response->toResponse($this->container);
            }
        }

        $this->emitter->emit($response);
    }

    public function run(
        ?RequestInterface $request = null,
    ): void {
        if ($request !== null) {
            $this->container->bind($request);
        } else {
            $request = $this->container->resolve(Request::class);
        }

        try {
            if (!isset($this->router)) {
                throw HttpException::fromInternalServerError();
            }

            $dispatchableRoute = $this->router->findByRequest(
                request: $request,
            );

            if ($dispatchableRoute === null) {
                throw HttpException::fromNotFound();
            }

            $this->emitter->emit(
                response: $this->pipeline(
                    resolver: static function (ContainerInterface $container) use ($dispatchableRoute, $request): ResponseInterface {
                        $callback = [
                            $container->resolve($dispatchableRoute->route->controller),
                            $dispatchableRoute->route->action,
                        ];

                        if (!\is_callable($callback)) {
                            throw HttpException::fromInternalServerError();
                        }

                        $arguments = [];

                        if (\sizeof($dispatchableRoute->arguments) > 0) {
                            if ($dispatchableRoute->route->requestArgumentName !== null) {
                                $arguments[$dispatchableRoute->route->requestArgumentName] = $request;
                            }

                            foreach ($dispatchableRoute->route->arguments as $argument) {
                                $arguments[$argument->mappedName ?? $argument->node->name] = $argument->getValue(
                                    matches: $dispatchableRoute->arguments,
                                );
                            }
                        } else {
                            $arguments[] = $request;
                        }

                        $response = \call_user_func(
                            $callback,
                            ...$arguments,
                        );

                        if ($response instanceof ResponsableInterface) {
                            $response = $response->toResponse($container);
                        }

                        if (!$response instanceof ResponseInterface) {
                            throw HttpException::fromInternalServerError();
                        }

                        return $response;
                    },
                    middlewares: \array_reverse(
                        \array_merge(
                            $this->middleware,
                            $dispatchableRoute->route->middleware,
                        ),
                    ),
                    request: $request,
                ),
            );
        } catch (\Throwable $e) {
            $this->handleException($request, $e);
        }
    }

    /**
     * @param (\Closure(ContainerInterface): ResponseInterface) $resolver
     * @param array<(\Closure(): MiddlewareInterface)> $middlewares
     */
    private function pipeline(
        \Closure $resolver,
        array $middlewares,
        RequestInterface $request,
    ): ResponseInterface {
        $next = new class ($resolver, $this->container) implements MiddlewareInterface {
            /**
             * @param (\Closure(ContainerInterface): ResponseInterface) $resolver
             */
            public function __construct(
                private \Closure $resolver,
                private ContainerInterface $container,
            ) {
            }

            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                return ($this->resolver)($this->container);
            }
        };

        foreach ($middlewares as $middleware) {
            $next = new MiddlewareNode(
                current: $middleware,
                next: $next,
            );
        }

        return $next->handle($request, $next);
    }
}
