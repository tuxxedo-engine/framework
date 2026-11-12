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

namespace Tuxxedo\Logger\Processor\Info;

use Tuxxedo\Http\Method;

readonly class HttpRequestInfo implements HttpRequestInfoInterface
{
    public function __construct(
        public readonly Method $method,
        public readonly string $path,
        public readonly string $host,
        public readonly string $clientIp,
        public readonly ?string $route,
    ) {
    }
}
