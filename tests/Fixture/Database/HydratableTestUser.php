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

namespace Fixture\Database;

use Tuxxedo\Database\Hydrator\HydratableInterface;

class HydratableTestUser implements HydratableInterface
{
    final public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
    ) {
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function create(
        array $properties,
    ): static {
        /** @var int $id */
        $id = $properties['id'];

        /** @var string $name */
        $name = $properties['name'];

        /** @var string|null $email */
        $email = $properties['email'] ?? null;

        return new static(
            id: $id,
            name: $name,
            email: $email,
        );
    }
}
