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

namespace Tuxxedo\Logger;

use Tuxxedo\Temporal\InstantInterface;

interface LogRecordInterface
{
    public string $message {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $context {
        get;
    }

    public LogLevel $level {
        get;
    }

    public InstantInterface $timestamp {
        get;
    }

    public ?string $channel {
        get;
    }

    public ?\Throwable $exception {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $extra {
        get;
    }

    public function withExtra(
        string $key,
        mixed $value,
    ): static;
}
