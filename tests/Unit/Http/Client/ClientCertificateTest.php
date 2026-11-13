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

namespace Unit\Http\Client;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\ClientCertificate;

class ClientCertificateTest extends TestCase
{
    public function testValues(): void
    {
        $certificate = new ClientCertificate('/certs/client.pem', '/certs/client.key', 'secret');

        self::assertSame('/certs/client.pem', $certificate->certificate);
        self::assertSame('/certs/client.key', $certificate->key);
        self::assertSame('secret', $certificate->passphrase);
    }

    public function testDefaults(): void
    {
        $certificate = new ClientCertificate('/certs/client.pem');

        self::assertNull($certificate->key);
        self::assertNull($certificate->passphrase);
    }
}
