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
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareNode;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponsableInterface;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseExceptionInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DispatchableRouteInterface;
use Tuxxedo\Router\RouterInterface;

class Kernel implements KernelInterface
{
    public private(set) array $middleware = [];
    public private(set) array $exceptionHandlers = [];
    public private(set) array $defaultExceptionHandlers = [];

    final public function __construct(
        public readonly ContainerInterface $container,
        public readonly ConfigInterface $config,
        public readonly ResponseEmitterInterface $emitter,
        public readonly DispatcherInterface $dispatcher,
        public readonly RouterInterface $router,
        public readonly string $appName = '',
        public readonly string $appVersion = '',
        public readonly Profile $appProfile = Profile::RELEASE,
    ) {
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
            $response = $e->toResponse();
        } else {
            $response = HttpException::fromInternalServerError()->toResponse();
        }

        foreach ($handlers as $handler) {
            $response = $this->container->call($handler)->handle($request, $response, $e);

            if ($response instanceof ResponsableInterface) {
                $response = $response->toResponse($this->container);
            }
        }

        $this->emitter->emit($response);
    }

    public function run(
        ?RequestInterface $request = null,
    ): void {
        $request ??= $this->container->resolve(RequestInterface::class);

        try {
            $dispatchableRoute = $this->router->findByRequest(
                request: $request,
            );

            if ($dispatchableRoute === null) {
                throw HttpException::fromNotFound();
            }

            $request = $request->withRoute($dispatchableRoute);

            $this->container->persistent($request);

            $this->emitter->emit(
                response: $this->pipeline(
                    middlewares: \array_reverse(
                        \array_merge(
                            $this->middleware,
                            $dispatchableRoute->route->middleware,
                        ),
                    ),
                    dispatchableRoute: $dispatchableRoute,
                    request: $request,
                ),
            );
        } catch (\Throwable $e) {
            $this->handleException($request, $e);
        }
    }

    /**
     * @param array<(\Closure(): MiddlewareInterface)> $middlewares
     */
    private function pipeline(
        array $middlewares,
        DispatchableRouteInterface $dispatchableRoute,
        RequestInterface $request,
    ): ResponseInterface {
        $next = new readonly class ($dispatchableRoute, $this->dispatcher, $this->container) implements MiddlewareInterface {
            public function __construct(
                private DispatchableRouteInterface $dispatchableRoute,
                private DispatcherInterface $dispatcher,
                private ContainerInterface $container,
            ) {
            }

            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                return $this->dispatcher->dispatch(
                    container: $this->container,
                    dispatchableRoute: $this->dispatchableRoute,
                    request: $request,
                );
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
