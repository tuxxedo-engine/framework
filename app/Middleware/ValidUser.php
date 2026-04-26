<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Model\ModelsManagerInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class ValidUser implements MiddlewareInterface
{
    public function __construct(
        protected readonly ContainerInterface $container,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        // @todo This could use a better API for fetching a named route argument, the current form is not very robust
        //       and very easily error prone when modifying the arguments to the route
        if (!$this->container->resolve(ModelsManagerInterface::class)->existsByIdentifier(User::class, $request->route->arguments[0])) {
            throw HttpException::fromNotFound();
        }

        return $next->handle($request, $next);
    }
}
