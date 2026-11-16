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

namespace Tuxxedo\Model;

/**
 * @template-covariant TModel of object
 */
interface PendingRelationEntryInterface
{
    /**
     * @var TModel|null
     */
    public ?object $item {
        get;
    }

    /**
     * @var non-empty-array<string, string|int|float|bool>|null
     */
    public ?array $key {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $pivot {
        get;
    }
}
