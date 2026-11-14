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

namespace Tuxxedo\Http\Client\Response;

use Tuxxedo\Http\Response\ResponseCodeInterface;
use Tuxxedo\Http\Url\UrlInterface;

interface ClientResponseHopInterface extends ResponseCodeInterface
{
    public UrlInterface $url {
        get;
    }

    public int $statusCode {
        get;
    }

    public ?string $primaryIp {
        get;
    }

    public ClientResponseTimingsInterface $timings {
        get;
    }
}
