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
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;

readonly class TransportOptions implements TransportOptionsInterface
{
    public DurationInterface $connectTimeout;
    public DurationInterface $timeout;

    /**
     * @var (\Closure(string): bool)|null
     */
    public ?\Closure $addressCheck;

    /**
     * @var (\Closure(ClientProgressInterface): mixed)|null
     */
    public ?\Closure $progress;

    /**
     * @param (\Closure(string): bool)|null $addressCheck
     * @param (\Closure(ClientProgressInterface): mixed)|null $progress
     */
    public function __construct(
        ?DurationInterface $connectTimeout = null,
        ?DurationInterface $timeout = null,
        public ?DurationInterface $idleTimeout = null,
        public ?int $maxResponseSize = 16 * 1024 * 1024,
        public ?HttpVersion $httpVersion = null,
        public ?ProxyInterface $proxy = null,
        public ?ClientCertificateInterface $clientCertificate = null,
        public ?string $certificateAuthority = null,
        ?\Closure $addressCheck = null,
        ?\Closure $progress = null,
        public ?string $sink = null,
    ) {
        $this->connectTimeout = $connectTimeout ?? Duration::fromSeconds(10);
        $this->timeout = $timeout ?? Duration::fromSeconds(30);
        $this->addressCheck = $addressCheck;
        $this->progress = $progress;
    }
}
