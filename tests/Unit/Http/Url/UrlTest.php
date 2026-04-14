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

namespace Unit\Http\Url;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Url\Url;

class UrlTest extends TestCase
{
    public function testGet(): void
    {
        $url = new Url('https://tuxxedo.dev/');

        self::assertSame($url->base, 'https://tuxxedo.dev/');
        self::assertSame($url->get(''), 'https://tuxxedo.dev/');
        self::assertSame($url->get('/'), 'https://tuxxedo.dev/');
        self::assertSame($url->get('docs'), 'https://tuxxedo.dev/docs');
        self::assertSame($url->get('/docs'), 'https://tuxxedo.dev/docs');
        self::assertSame($url->get('download/latest'), 'https://tuxxedo.dev/download/latest');
        self::assertSame($url->get('dynamic?page=admin'), 'https://tuxxedo.dev/dynamic?page=admin');
    }
}
