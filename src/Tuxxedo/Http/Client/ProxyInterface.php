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

use Tuxxedo\Http\Url\UrlInterface;

interface ProxyInterface
{
    public UrlInterface $url {
        get;
    }

    public ?string $username {
        get;
    }

    public ?string $password {
        get;
    }

    /**
     * @var list<string>
     */
    public array $noProxy {
        get;
    }
}
