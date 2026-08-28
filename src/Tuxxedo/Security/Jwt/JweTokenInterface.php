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

namespace Tuxxedo\Security\Jwt;

interface JweTokenInterface extends TokenInterface
{
    public string $encryptedKey {
        get;
    }

    public string $initializationVector {
        get;
    }

    public string $ciphertext {
        get;
    }

    public string $authenticationTag {
        get;
    }
}
