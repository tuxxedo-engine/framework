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

namespace Tuxxedo\Http\Response\Stream;

readonly class SseEvent implements SseEventInterface
{
    /**
     * @param positive-int|null $retry
     */
    final private function __construct(
        public readonly ?string $data = null,
        public readonly ?string $id = null,
        public readonly ?string $event = null,
        public readonly ?int $retry = null,
        public readonly ?string $comment = null,
    ) {
    }

    /**
     * @param positive-int|null $retry
     */
    public static function create(
        string $data,
        ?string $id = null,
        ?string $event = null,
        ?int $retry = null,
    ): self {
        return new self(
            data: $data,
            id: $id,
            event: $event,
            retry: $retry,
        );
    }

    /**
     * @param positive-int|null $retry
     */
    public static function json(
        mixed $data,
        ?string $id = null,
        ?string $event = null,
        ?int $retry = null,
    ): self {
        return new self(
            data: \json_encode($data, \JSON_THROW_ON_ERROR),
            id: $id,
            event: $event,
            retry: $retry,
        );
    }

    public static function comment(
        string $comment,
    ): self {
        return new self(
            comment: $comment,
        );
    }

    public static function keepalive(): self
    {
        return new self(
            comment: 'keepalive',
        );
    }
}
