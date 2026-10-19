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

namespace Tuxxedo\Cache\Driver\Redis\Config;

use Tuxxedo\Cache\Config\CacheDriverConfigInterface;

interface RedisCacheDriverConfigInterface extends CacheDriverConfigInterface
{
    public string $host {
        get;
    }

    public int $port {
        get;
    }

    public int $database {
        get;
    }

    public ?string $username {
        get;
    }

    public ?string $password {
        get;
    }

    public float $timeout {
        get;
    }

    public bool $persistent {
        get;
    }

    public bool $tls {
        get;
    }

    public bool $tlsVerifyPeer {
        get;
    }
}
