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

use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Http\SameSite;

require __DIR__ . '/../../../../vendor/autoload.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = \is_string($uri)
    ? \parse_url($uri, \PHP_URL_PATH)
    : '/';

if ($path === '/replaces-existing') {
    \header('X-Custom: stale');
}

$response = match ($path) {
    '/repeated-headers' => new Response(
        headers: [
            new Header('Link', '</a.css>; rel=preload'),
            new Header('X-Custom', 'value'),
            new Header('link', '</b.js>; rel=preload'),
        ],
    ),
    '/replaces-existing' => new Response(
        headers: [
            new Header('X-Custom', 'fresh'),
        ],
    ),
    '/cookies' => new Response(
        headers: [
            new Cookie(
                name: 'first',
                value: '1',
                expires: 0,
            ),
            new Header('Set-Cookie', 'raw=2; Path=/'),
            new Header('Set-Cookie', 'other=3; Path=/'),
            new Cookie(
                name: 'last',
                value: '4',
                expires: 0,
                sameSite: SameSite::STRICT,
            ),
            new Cookie(
                name: 'cross',
                value: '5',
                expires: 0,
                secure: true,
                sameSite: SameSite::NONE,
            ),
        ],
    ),
    '/status' => new Response(
        body: 'missing',
        responseCode: ResponseCode::NOT_FOUND,
    ),
    '/content-length' => new Response(
        body: 'hello world',
        headers: [
            new Header('Content-Length', '5'),
        ],
    ),
    default => new Response(
        responseCode: ResponseCode::NOT_FOUND,
    ),
};

(new ResponseEmitter())->emit($response);
