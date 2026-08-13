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

namespace Unit\Mail\Transport\SmtpMail\Xoauth;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Transport\SmtpMail\Xoauth\StaticXoauthTokenProvider;

class StaticXoauthTokenProviderTest extends TestCase
{
    public function testGetTokenReturnsInjectedToken(): void
    {
        $provider = new StaticXoauthTokenProvider(
            token: 'static-token',
        );

        self::assertSame(
            'static-token',
            $provider->getToken(),
        );
    }

    public function testGetTokenIsStableAcrossCalls(): void
    {
        $provider = new StaticXoauthTokenProvider(
            token: 'abc',
        );

        self::assertSame('abc', $provider->getToken());
        self::assertSame('abc', $provider->getToken());
    }
}
