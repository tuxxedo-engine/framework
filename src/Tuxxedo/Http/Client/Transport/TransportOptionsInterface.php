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

namespace Tuxxedo\Http\Client\Transport;

use Tuxxedo\Http\Client\ClientCertificateInterface;
use Tuxxedo\Http\Client\ClientProgressInterface;
use Tuxxedo\Http\Client\ProxyInterface;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Temporal\DurationInterface;

interface TransportOptionsInterface
{
    public DurationInterface $connectTimeout {
        get;
    }

    public DurationInterface $timeout {
        get;
    }

    public ?DurationInterface $idleTimeout {
        get;
    }

    public ?int $maxResponseSize {
        get;
    }

    public ?HttpVersion $httpVersion {
        get;
    }

    public ?ProxyInterface $proxy {
        get;
    }

    public ?ClientCertificateInterface $clientCertificate {
        get;
    }

    public ?string $certificateAuthority {
        get;
    }

    /**
     * @var (\Closure(string): bool)|null
     */
    public ?\Closure $addressCheck {
        get;
    }

    /**
     * @var (\Closure(ClientProgressInterface): mixed)|null
     */
    public ?\Closure $progress {
        get;
    }

    public ?string $sink {
        get;
    }
}
