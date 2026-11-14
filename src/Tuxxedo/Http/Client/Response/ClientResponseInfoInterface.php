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

use Tuxxedo\Http\Url\UrlInterface;

interface ClientResponseInfoInterface
{
    public UrlInterface $effectiveUrl {
        get;
    }

    public ?string $primaryIp {
        get;
    }

    public ?int $primaryPort {
        get;
    }

    public bool $reusedConnection {
        get;
    }

    public int $uploadedBytes {
        get;
    }

    public int $downloadedBytes {
        get;
    }

    public ClientResponseTimingsInterface $timings {
        get;
    }

    /**
     * @var list<ClientResponseHopInterface>
     */
    public array $hops {
        get;
    }

    public int $redirectCount {
        get;
    }

    public bool $redirected {
        get;
    }
}
