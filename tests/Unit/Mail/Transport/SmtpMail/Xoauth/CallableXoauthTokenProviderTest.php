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
use Tuxxedo\Mail\Transport\SmtpMail\Xoauth\CallableXoauthTokenProvider;

class CallableXoauthTokenProviderTest extends TestCase
{
    public function testGetTokenInvokesTheProvidedFetcher(): void
    {
        $provider = new CallableXoauthTokenProvider(
            fetcher: static fn (): string => 'issued-token',
        );

        self::assertSame(
            'issued-token',
            $provider->getToken(),
        );
    }

    public function testGetTokenReflectsFetcherReturnOnEachCall(): void
    {
        $calls = 0;
        $provider = new CallableXoauthTokenProvider(
            fetcher: static function () use (&$calls): string {
                $calls++;

                return 'token-' . $calls;
            },
        );

        self::assertSame('token-1', $provider->getToken());
        self::assertSame('token-2', $provider->getToken());
    }
}
