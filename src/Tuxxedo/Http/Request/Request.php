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

use Tuxxedo\Http\HeaderInterface;

class Request implements RequestInterface
{
    public function __construct(
        public private(set) ServerContextInterface $server,
        public private(set) HeaderContextInterface $headers,
        public private(set) InputContextInterface $cookies,
        public private(set) InputContextInterface $get,
        public private(set) InputContextInterface $post,
    ) {
    }

    public static function createFromEnvironment(): Request
    {
        return new Request(
            server: new EnvironmentServerContext(),
            headers: new EnvironmentHeaderContext(),
            cookies: new EnvironmentInputContext(
                superglobal: \INPUT_COOKIE,
            ),
            get: new EnvironmentInputContext(
                superglobal: \INPUT_GET,
            ),
            post: new EnvironmentInputContext(
                superglobal: \INPUT_POST,
            ),
        );
    }
}
