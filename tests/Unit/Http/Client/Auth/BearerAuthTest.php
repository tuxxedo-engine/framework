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
use Tuxxedo\Http\Client\Auth\BearerAuth;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;

class BearerAuthTest extends TestCase
{
    public function testReplacesTheAuthorizationHeader(): void
    {
        $request = new BearerAuth('token')->apply(
            new ClientRequest(
                method: Method::GET,
                url: 'https://api.example/',
                headers: [
                    new Header('Authorization', 'Basic old'),
                ],
            ),
            AuthClient::create(new ScriptedTransport()),
        );

        self::assertSame('Bearer token', AuthClient::header($request, 'Authorization'));
    }
}
