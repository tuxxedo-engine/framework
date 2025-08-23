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

use Tuxxedo\Application\ExtensionInterface;
use Tuxxedo\Application\Profile;
use Tuxxedo\Application\ServiceProviderInterface;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Discovery\DiscoveryChannelInterface;
use Tuxxedo\Discovery\DiscoveryType;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewarePipeline;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponsableInterface;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseExceptionInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\RouterInterface;

class Kernel implements HttpApplicationInterface
{
    public readonly ConfigInterface $config;
    public readonly ContainerInterface $container;

    /**
     * @var array<(\Closure(): MiddlewareInterface)>
     */
    private array $middleware = [];

    /**
     * @var array<class-string<\Throwable>, array<\Closure(): ErrorHandlerInterface>>
     */
    private array $exceptions = [];

    public private(set) ResponseEmitterInterface $emitter;
    public private(set) RouterInterface $router;

    /**
     * @var array<(\Closure(): ErrorHandlerInterface)>
     */
    private array $defaultExceptionHandlers = [];

    final public function __construct(
        public readonly string $appName = '',
        public readonly string $appVersion = '',
        public readonly Profile $appProfile = Profile::RELEASE,
        ?ContainerInterface $container = null,
        ?Config $config = null,
    ) {
        $this->config = $config ?? new Config();
        $this->container = $container ?? new Container();

        $this->container->bind($this);
        $this->container->bind($this->config);
        $this->container->bind($this->container);

        $this->emitter = new ResponseEmitter();
    }

    /**
     * @param ServiceProviderInterface|(\Closure(): ServiceProviderInterface) $provider
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

    public function discover(
        DiscoveryChannelInterface $channel,
        ?DiscoveryType $discoveryType = null,
    ): static {
        if ($discoveryType !== null) {
            $types = \array_filter(
                $channel->provides(),
                static fn (DiscoveryType $channelDiscoveryType): bool => $channelDiscoveryType === $discoveryType,
            );
        } else {
            $types = $channel->provides();
        }

        foreach ($types as $type) {
            foreach ($channel->discover($type) as $discovery) {
                switch ($type) {
                    case DiscoveryType::EXTENSIONS:
                        /** @var class-string<ExtensionInterface> $discovery */
                        $this->container->resolve($discovery)->augment($this);
                        break;
                    case DiscoveryType::MIDDLEWARE:
                        /** @var class-string<MiddlewareInterface> $discovery */
                        $this->middleware(
                            fn (): MiddlewareInterface => $this->container->resolve($discovery),
                        );
                        break;
                    case DiscoveryType::SERVICES:
                        /** @var class-string<ServiceProviderInterface> $discovery */
                        $this->serviceProvider(
                            fn (): ServiceProviderInterface => $this->container->resolve($discovery),
                        );
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * @param (\Closure(): MiddlewareInterface)|MiddlewareInterface $middleware
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
                response: (new MiddlewarePipeline(
                    container: $this->container,
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
                    middleware: \array_reverse(
                        \array_merge(
                            $this->middleware,
                            $dispatchableRoute->route->middleware,
                        ),
                    ),
                ))->run($request),
            );
        } catch (\Throwable $e) {
            $this->handleException($request, $e);
        }
    }
}
