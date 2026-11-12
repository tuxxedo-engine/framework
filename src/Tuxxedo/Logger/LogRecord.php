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

class LogRecord implements LogRecordInterface
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public readonly string $message,
        public readonly LogLevel $level,
        public readonly InstantInterface $timestamp,
        public readonly array $context = [],
        public readonly ?string $channel = null,
        public readonly ?\Throwable $exception = null,
        public readonly array $extra = [],
    ) {
    }

    public function withExtra(
        string $key,
        mixed $value,
    ): static {
        return clone (
            $this,
            [
                'extra' => [
                    ...$this->extra,
                    $key => $value,
                ],
            ],
        );
    }
}
