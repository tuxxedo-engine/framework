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
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\Response\ResponseEmitterInterface;
use Tuxxedo\Http\Response\ResponseExceptionInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\RouterInterface;

class Kernel
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
        ?ContainerInterface $container = null,
        ?Config $config = null,
    ) {
        $this->config = $config ?? new Config();
        $this->container = $container ?? new Container();

        $this->container->bind($this);
        $this->container->bind($this->container);

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

    public function discover(
        DiscoveryChannelInterface $channel,
        ?DiscoveryType $type = null,
    ): static {
        if ($type !== null) {
            $types = \array_filter(
                $channel->provides(),
                static fn (DiscoveryType $discoveryType): bool => $discoveryType === $type,
            );
        } else {
            $types = $channel->provides();
        }

        foreach ($types as $type) {
            foreach ($channel->discover($type) as $discovery) {
                switch ($type) {
                    case DiscoveryType::EXTENSIONS:
                        /** @var class-string<ExtensionInterface> $discovery */
                        (new $discovery())->augment($this);
                        break;
                    case DiscoveryType::SERVICES:
                        /** @var class-string<ServiceProviderInterface> $discovery */
                        $this->serviceProvider(
                            static fn (): ServiceProviderInterface => new $discovery(),
                        );
                        break;
                }
            }
        }

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
                    resolver: static function (ContainerInterface $container) use ($route, $request): ResponseInterface {
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
