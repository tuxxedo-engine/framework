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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\Url\UrlInterface;

interface CookieJarInterface
{
    /**
     * @var CookieInterface[]
     */
    public array $cookies {
        get;
    }

    /**
     * @param CookieInterface[] $cookies
     */
    public function store(
        array $cookies,
        UrlInterface $url,
    ): void;

    public function header(
        UrlInterface $url,
    ): ?string;

    public function clear(): void;
}
