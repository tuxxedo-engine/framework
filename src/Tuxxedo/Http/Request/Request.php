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

use Tuxxedo\Mapper\MapperInterface;

readonly class Request implements RequestInterface
{
    public ServerContextInterface $server;
    public HeaderContextInterface $headers;
    public InputContextInterface $cookies;
    public InputContextInterface $get;
    public InputContextInterface $post;

    public function __construct(
        MapperInterface $mapper,
    ) {
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
    }
}
