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
use Tuxxedo\Http\Client\Auth\HeaderAuth;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;

class HeaderAuthTest extends TestCase
{
    public function testReplacesTheHeader(): void
    {
        $request = new HeaderAuth('X-Api-Key', 'secret')->apply(
            new ClientRequest(
                method: Method::GET,
                url: 'https://api.example/',
                headers: [
                    new Header('x-api-key', 'old'),
                ],
            ),
            AuthClient::create(new ScriptedTransport()),
        );

        self::assertSame('secret', AuthClient::header($request, 'X-Api-Key'));
        self::assertSame(1, \sizeof($request->headers));
    }
}
