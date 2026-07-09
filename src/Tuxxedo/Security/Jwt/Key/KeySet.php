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

namespace Tuxxedo\Security\Jwt\Key;

class KeySet implements KeySetInterface
{
    /**
     * @var list<KeyInterface>
     */
    public readonly array $keys;

    public function __construct(
        KeyInterface ...$keys,
    ) {
        $this->keys = \array_values($keys);
    }

    public function find(
        string $keyId,
    ): ?KeyInterface {
        foreach ($this->keys as $key) {
            if ($key->keyId === $keyId) {
                return $key;
            }
        }

        return null;
    }
}
