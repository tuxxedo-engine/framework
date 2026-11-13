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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Url\UrlInterface;

readonly class ClientResponseHop implements ClientResponseHopInterface
{
    public function __construct(
        public UrlInterface $url,
        public ResponseCode $responseCode,
        public int $statusCode,
        public ?string $primaryIp,
        public ClientResponseTimingsInterface $timings,
    ) {
    }
}
