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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\WeightedHeader;

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

    public HttpVersion $protocolVersion {
        get {
            /** @var string|null $protocol */
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? null;

            return $protocol !== null
                ? (HttpVersion::tryFrom($protocol) ?? HttpVersion::V1_1)
                : HttpVersion::V1_1;
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
            return $_SERVER['HTTP_USER_AGENT'];
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

    public string $ipAddress {
        get {
            /** @var string */
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    public ?string $preferredLanguage {
        get {
            return $this->preferredLanguages[0] ?? null;
        }
    }

    public array $preferredLanguages {
        get {
            static $value = null;

            if ($value === null) {
                /** @var string $header */
                $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

                if ($header === '') {
                    return [];
                }

                $value = (new WeightedHeader('Accept-Language', $header))->getWeightedOrder();
            }

            /** @var string[] */
            return $value;
        }
    }
}
