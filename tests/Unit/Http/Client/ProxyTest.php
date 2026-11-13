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
use Support\Env\Source\StubEnvSource;
use Tuxxedo\Env\Env;
use Tuxxedo\Http\Client\Proxy;
use Tuxxedo\Http\Url\Url;

class ProxyTest extends TestCase
{
    public function testDefaults(): void
    {
        $proxy = new Proxy('http://proxy.internal:3128');

        self::assertSame('proxy.internal', $proxy->url->host);
        self::assertSame(3128, $proxy->url->port);
        self::assertNull($proxy->username);
        self::assertNull($proxy->password);
        self::assertSame([], $proxy->noProxy);
    }

    public function testValues(): void
    {
        $url = Url::parse('http://proxy.internal:3128');
        $proxy = new Proxy(
            url: $url,
            username: 'user',
            password: 'secret',
            noProxy: [
                'localhost',
                '.internal',
            ],
        );

        self::assertSame($url, $proxy->url);
        self::assertSame('user', $proxy->username);
        self::assertSame('secret', $proxy->password);
        self::assertSame(
            [
                'localhost',
                '.internal',
            ],
            $proxy->noProxy,
        );
    }

    public function testFromEnv(): void
    {
        $env = new Env(
            new StubEnvSource(
                [
                    'HTTPS_PROXY' => 'proxy.internal:3128',
                    'http_proxy' => 'http://user:secret@plain.internal:8080',
                    'NO_PROXY' => 'localhost, .internal,,',
                ],
            ),
        );

        $https = Proxy::fromEnv($env);
        $http = Proxy::fromEnv($env, 'http');

        self::assertSame('http://proxy.internal:3128', (string) $https?->url);
        self::assertSame(
            [
                'localhost',
                '.internal',
            ],
            $https?->noProxy,
        );

        self::assertSame('plain.internal', $http?->url->host);
        self::assertSame('user', $http->url->user);
    }

    public function testFromEnvWithoutAProxy(): void
    {
        self::assertNull(Proxy::fromEnv(new Env(new StubEnvSource([]))));
        self::assertSame(
            [
                'a',
            ],
            Proxy::fromEnv(
                new Env(
                    new StubEnvSource(
                        [
                            'https_proxy' => 'http://proxy.internal',
                            'no_proxy' => 'a',
                        ],
                    ),
                ),
            )?->noProxy,
        );
    }
}
