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

/**
 * @template-covariant T
 */
interface PendingResponseInterface
{
    public bool $completed {
        get;
    }

    public bool $succeeded {
        get;
    }

    public ?\Throwable $exception {
        get;
    }

    /**
     * @return T
     *
     * @throws \Throwable
     */
    public function result(): mixed;
}
