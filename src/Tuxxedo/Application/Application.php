<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2024 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Application;

use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ServiceLoader;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\ResponseCode;

class Application
{
    public readonly Container $container;

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
        //       that affects the error handling

        // @todo Register Request and Response objects here, unless they are passed in directly

        // @todo Register the Router, this needs some sort of configuration for how to intercept
        //       the incoming URI to parse, e.g. PATH_INFO for php -S, auto detection may be
        //       possible via the previous registered Request object.

        // @todo Once the router is registered, look into the routes and where it retrieve its
        //       internal database, which could for example be static, app/routes.php,
        //       static attributes (via precompiled file) or dynamic attributes via reflection
    }

    public function dispatch(DispatchableRoute|string $route): void
    {
        // @todo Implement Dispatching logic here by resolving the router, looking up the input
        //       from the current request, error handling and then initializing the controller
        //       code. This needs some extra thought for how the best possible way to avoid
        //       adding boilerplate code for things like
        if (!$route instanceof DispatchableRoute) {
            $route = $this->container->resolve(RouterInterface::class)->getByName($route);
        }

        $route->dispatch($this);
    }

    /**
     * @throws \Exception
     */
    public function run(?string $uri = null): void
    {
        // @todo Implement Dispatching logic here by resolving the router, looking up the input
        //       from the current request, error handling and then initializing the controller
        //       code. This needs some extra thought for how the best possible way to avoid
        //       adding boilerplate code for things like
        if ($uri === null) {
            $uri = $this->container->resolve(RequestInterface::class)->getUri();
        }

        // @todo This may need some sort of handling for outputs returned as a return value instead
        //       of expecting that to be sent directly to STDOUT
        $this->dispatch($this->container->resolve(RouterInterface::class)->getRouteByUri($uri));
    }
}
