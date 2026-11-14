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

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = \is_string($uri)
    ? (string) \parse_url($uri, \PHP_URL_PATH)
    : '/';

$query = [];

\parse_str((string) \parse_url(\is_string($uri) ? $uri : '/', \PHP_URL_QUERY), $query);

$parameter = static function (string $name, int $default) use ($query): int {
    $value = $query[$name] ?? null;

    return \is_string($value) && \preg_match('/^\d+$/', $value) === 1
        ? (int) $value
        : $default;
};

$send = static function (string $chunk): void {
    echo $chunk;

    \flush();
};

if (\preg_match('#^/status/(\d{3})$#', $path, $matches) === 1) {
    \http_response_code((int) $matches[1]);

    echo 'status ' . $matches[1];

    return true;
}

if ($path === '/echo') {
    $headers = [];

    foreach ($_SERVER as $name => $value) {
        if (\is_string($name) && \str_starts_with($name, 'HTTP_')) {
            $headers[\strtolower(\str_replace('_', '-', \substr($name, 5)))] = $value;
        }
    }

    \header('Content-Type: application/json');

    echo \json_encode(
        [
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'query' => $query,
            'headers' => $headers,
            'contentType' => $_SERVER['CONTENT_TYPE'] ?? null,
            'body' => (string) \file_get_contents('php://input'),
        ],
        \JSON_THROW_ON_ERROR,
    );

    return true;
}

if ($path === '/redirect') {
    \header('Location: /echo?redirected=1', true, 302);

    return true;
}

if ($path === '/cookie') {
    \header('Set-Cookie: session=abc; Path=/');

    echo 'cookie';

    return true;
}

if ($path === '/slow') {
    \usleep($parameter('ms', 1000) * 1000);

    echo 'slow';

    return true;
}

if ($path === '/stall') {
    $send('partial');

    \sleep($parameter('seconds', 3));

    echo 'rest';

    return true;
}

if ($path === '/big') {
    echo \str_repeat('a', $parameter('size', 1024));

    return true;
}

if ($path === '/chunks') {
    foreach (
        [
            'one',
            'two',
            'three',
        ] as $chunk
    ) {
        $send($chunk);

        \usleep(100_000);
    }

    return true;
}

if ($path === '/sse') {
    \header('Content-Type: text/event-stream');

    $send("event: greeting\ndata: hello\n\n");
    $send("data: world\nid: 2\n\n");

    return true;
}

if ($path === '/csv') {
    \header('Content-Type: text/csv');

    $send("name,age\n");
    $send("John,30\n");
    $send("Jane,40\n");

    return true;
}

if ($path === '/gzip') {
    \header('Content-Encoding: gzip');
    \header('Content-Type: text/plain');

    echo \gzencode('compressed');

    return true;
}

\http_response_code(404);

echo 'not found';

return true;
