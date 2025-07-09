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

namespace App\Middleware;

use App\Services\Logger\LoggerInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(\Attribute::TARGET_METHOD)]
class LoggerMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected readonly ContainerInterface $container,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $this->container->resolve(LoggerInterface::class)->log(
            entry: \sprintf(
                'Middleware: %s',
                static::class,
            ),
        );

        return $next->handle($request, $next);
    }
}
