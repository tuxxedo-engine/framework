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

namespace Unit\Http\Client\Transport;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\ClientCertificate;
use Tuxxedo\Http\Client\Proxy;
use Tuxxedo\Http\Client\Transport\TransportOptions;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Temporal\Duration;

class TransportOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new TransportOptions();

        self::assertSame(10, $options->connectTimeout->seconds);
        self::assertSame(30, $options->timeout->seconds);
        self::assertNull($options->idleTimeout);
        self::assertSame(16 * 1024 * 1024, $options->maxResponseSize);
        self::assertNull($options->httpVersion);
        self::assertNull($options->proxy);
        self::assertNull($options->clientCertificate);
        self::assertNull($options->certificateAuthority);
        self::assertNull($options->addressCheck);
    }

    public function testValues(): void
    {
        $proxy = new Proxy('http://proxy.internal:3128');
        $certificate = new ClientCertificate('/certs/client.pem');
        $check = static fn (string $ip): bool => $ip !== '127.0.0.1';
        $options = new TransportOptions(
            connectTimeout: Duration::fromSeconds(1),
            timeout: Duration::fromSeconds(2),
            idleTimeout: Duration::fromSeconds(3),
            maxResponseSize: null,
            httpVersion: HttpVersion::V1_1,
            proxy: $proxy,
            clientCertificate: $certificate,
            certificateAuthority: '/certs/ca.pem',
            addressCheck: $check,
        );

        self::assertSame(1, $options->connectTimeout->seconds);
        self::assertSame(2, $options->timeout->seconds);
        self::assertSame(3, $options->idleTimeout?->seconds);
        self::assertNull($options->maxResponseSize);
        self::assertSame(HttpVersion::V1_1, $options->httpVersion);
        self::assertSame($proxy, $options->proxy);
        self::assertSame($certificate, $options->clientCertificate);
        self::assertSame('/certs/ca.pem', $options->certificateAuthority);
        self::assertSame($check, $options->addressCheck);
    }
}
