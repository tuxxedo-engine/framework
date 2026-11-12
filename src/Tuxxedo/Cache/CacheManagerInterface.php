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

namespace Tuxxedo\Cache;

interface CacheManagerInterface extends CacheInterface
{
    public CacheInterface $root {
        get;
    }

    /**
     * @var CacheInterface[]
     */
    public array $scopes {
        get;
    }

    public ?string $separator {
        get;
    }

    /**
     * @var list<array-key>|null
     */
    public ?array $isolatedScopes {
        get;
    }

    /**
     * @throws CacheException
     */
    public function scope(
        string $name,
    ): CacheInterface;
}
