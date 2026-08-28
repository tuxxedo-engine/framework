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

namespace Tuxxedo\Security\Jwt\Decrypter;

use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class DirectDecrypter implements DecrypterInterface
{
    public function __construct(
        private readonly SymmetricKey $key,
    ) {
    }

    public function unwrapKey(
        string $wrappedKey,
    ): string {
        return $this->key->secret;
    }
}
