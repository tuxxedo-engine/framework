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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\RouterException;
use Tuxxedo\Router\RouterInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class RequestFunctions
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RouterInterface $router,
        private readonly UrlInterface $url,
    ) {
    }

    #[LumiFunction('request')]
    public function request(): RequestInterface
    {
        return $this->request;
    }

    #[LumiFunction('url')]
    public function url(
        string $path,
    ): string {
        return $this->url->get($path);
    }

    /**
     * @param string|array<string, string>|null $name
     * @param array<string, string> $args
     */
    #[LumiFunction('route')]
    public function route(
        string|array|null $name = null,
        array $args = [],
    ): string {
        if (\is_array($name)) {
            $args = $name;
            $name = null;
        }

        if ($name === null) {
            $currentRoute = $this->request->route;

            if ($args !== []) {
                return (new DispatchableRoute(
                    route: $currentRoute->route,
                    arguments: \array_merge($currentRoute->arguments, $args),
                ))->asUrl() ?? throw HttpException::fromInternalServerError();
            }

            return $currentRoute->asUrl() ?? throw HttpException::fromInternalServerError();
        }

        return $this->router->findByName($name, $args)?->asUrl() ?? throw RouterException::fromInvalidNamedRoute(
            name: $name,
        );
    }
}
