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

namespace Tuxxedo\Http\Client\Cache;

use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\HttpVersion;

interface CacheEntryInterface
{
    public int $statusCode {
        get;
    }

    public HttpVersion $httpVersion {
        get;
    }

    /**
     * @var list<array{0: string, 1: string}>
     */
    public array $headers {
        get;
    }

    public string $body {
        get;
    }

    public float $freshUntil {
        get;
    }

    /**
     * @var array<string, string>
     */
    public array $vary {
        get;
    }

    public ?string $etag {
        get;
    }

    public ?string $lastModified {
        get;
    }

    public function matches(
        ClientRequestInterface $request,
    ): bool;

    public function toResponse(
        ClientRequestInterface $request,
    ): ClientResponseInterface;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
