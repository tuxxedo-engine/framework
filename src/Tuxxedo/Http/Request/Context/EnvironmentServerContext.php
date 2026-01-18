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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\Method;

class EnvironmentServerContext implements ServerContextInterface
{
    public bool $https {
        get {
            return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }
    }

    public bool $ajax {
        get {
            return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
        }
    }

    public Method $method {
        get {
            /** @var string $method */
            $method = $_SERVER['REQUEST_METHOD'];

            return Method::from($method);
        }
    }

    public string $uri {
        get {
            /** @var string */
            return $_SERVER['PATH_INFO'] ?? '/';
        }
    }

    public string $fullUri {
        get {
            /** @var string */
            return $_SERVER['REQUEST_URI'];
        }
    }

    public string $queryString {
        get {
            /** @var string */
            return $_SERVER['QUERY_STRING'] ?? '';
        }
    }

    public string $userAgent {
        get {
            /** @var string */
            return  $_SERVER['HTTP_USER_AGENT'];
        }
    }

    public string $host {
        get {
            /** @var string */
            return $_SERVER['SERVER_NAME'];
        }
    }

    public int $port {
        get {
            /** @var string */
            $port = $_SERVER['SERVER_PORT'];

            return (int) $port;
        }
    }
}
