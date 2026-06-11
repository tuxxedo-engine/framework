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

namespace Tuxxedo\Model\Hydrator\Coercer;

use Tuxxedo\Model\ModelException;

class JsonCoercer implements CoercerInterface
{
    public function __construct(
        private readonly int $flags = \JSON_THROW_ON_ERROR,
    ) {
    }

    public function hydrate(
        string|int|float|bool $value,
    ): mixed {
        if (!\is_string($value)) {
            throw ModelException::fromCoercionFailure(
                coercerClass: self::class,
                expectedType: 'string',
                actualType: \get_debug_type($value),
            );
        }

        return \json_decode(
            json: $value,
            associative: true,
            flags: $this->flags,
        );
    }

    public function dehydrate(
        mixed $value,
    ): string|int|float|bool {
        return \json_encode(
            value: $value,
            flags: $this->flags,
        );
    }
}
