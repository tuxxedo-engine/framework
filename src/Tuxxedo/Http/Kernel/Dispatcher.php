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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponsableInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DispatchableRouteInterface;

// @todo Consider whether we want this as the default or have a DI like default dispatcher
//       as this will also fix the oddity of the requestArgumentName
class Dispatcher implements DispatcherInterface
{
    /**
     * @throws HttpException
     */
    public function dispatch(
        ContainerInterface $container,
        DispatchableRouteInterface $dispatchableRoute,
        RequestInterface $request,
    ): ResponseInterface {
        $callback = [
            $container->resolve($dispatchableRoute->route->controller),
            $dispatchableRoute->route->action,
        ];

        if (!\is_callable($callback)) {
            throw HttpException::fromInternalServerError();
        }

        $arguments = [];

        if (\sizeof($dispatchableRoute->arguments) > 0) {
            foreach ($dispatchableRoute->route->arguments as $argument) {
                $arguments[$argument->mappedName ?? $argument->node->name] = $argument->getValue(
                    matches: $dispatchableRoute->arguments,
                );
            }
        }

        $response = $container->call($callback(...), $arguments);

        if ($response instanceof ResponsableInterface) {
            $response = $response->toResponse($container);
        }

        if (!$response instanceof ResponseInterface) {
            throw HttpException::fromInternalServerError();
        }

        return $response;
    }
}
