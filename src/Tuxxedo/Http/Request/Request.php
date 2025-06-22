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
    /**
     * @param HeaderContextInterface<array-key, HeaderInterface> $headers
     * @param HeaderContextInterface<string, string> $cookies
     */
    public function __construct(
        public private(set) ServerContextInterface $context,
        public private(set) HeaderContextInterface $headers,
        public private(set) HeaderContextInterface $cookies,
    ) {
    }

    public static function createFromEnvironment(): Request
    {
        return new Request(
            context: new EnvironmentServerContext(),
            headers: new EnvironmentHeaderContext(),
            cookies: new EnvironmentCookieContext(),
        );
    }
}
