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

namespace Unit\Http\Client\Auth;

use PHPUnit\Framework\TestCase;
use Support\Http\Client\AuthClient;
use Support\Http\Client\ScriptedTransport;
use Tuxxedo\Http\Client\Auth\BasicAuth;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Method;

class BasicAuthTest extends TestCase
{
    public function testSetsTheAuthorizationHeader(): void
    {
        $request = new BasicAuth('user', 'pa:ss')->apply(
            new ClientRequest(Method::GET, 'https://api.example/'),
            AuthClient::create(new ScriptedTransport()),
        );

        self::assertSame('Basic ' . \base64_encode('user:pa:ss'), AuthClient::header($request, 'Authorization'));
    }
}
