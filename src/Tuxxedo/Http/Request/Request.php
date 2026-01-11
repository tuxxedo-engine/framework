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

namespace Tuxxedo\Http\Request;

use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\EnvironmentBodyContext;
use Tuxxedo\Http\Request\Context\EnvironmentHeaderContext;
use Tuxxedo\Http\Request\Context\EnvironmentInputContext;
use Tuxxedo\Http\Request\Context\EnvironmentServerContext;
use Tuxxedo\Http\Request\Context\EnvironmentUploadedFilesContext;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Context\ServerContextInterface;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;
use Tuxxedo\Mapper\Mapper;
use Tuxxedo\Mapper\MapperInterface;
use Tuxxedo\Router\DispatchableRouteInterface;

class Request implements RequestInterface
{
    public readonly ServerContextInterface $server;
    public readonly HeaderContextInterface $headers;
    public readonly InputContextInterface $cookies;
    public readonly InputContextInterface $get;
    public readonly InputContextInterface $post;
    public readonly UploadedFilesContextInterface $files;
    public readonly BodyContextInterface $body;
    public private(set) DispatchableRouteInterface $route;

    public function __construct(
        ?DispatchableRouteInterface $route = null,
        ?MapperInterface $mapper = null,
    ) {
        $mapper ??= new Mapper();

        $this->server = new EnvironmentServerContext();
        $this->headers = new EnvironmentHeaderContext();

        $this->cookies = new EnvironmentInputContext(
            superglobal: \INPUT_COOKIE,
            mapper: $mapper,
        );

        $this->get = new EnvironmentInputContext(
            superglobal: \INPUT_GET,
            mapper: $mapper,
        );

        $this->post = new EnvironmentInputContext(
            superglobal: \INPUT_POST,
            mapper: $mapper,
        );

        $this->files = new EnvironmentUploadedFilesContext();

        $this->body = new EnvironmentBodyContext(
            mapper: $mapper,
        );

        if ($route !== null) {
            $this->route = $route;
        }
    }

    public function withRoute(
        DispatchableRouteInterface $route,
    ): static {
        return clone (
            $this,
            [
                'route' => $route,
            ],
        );
    }
}
