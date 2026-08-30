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

namespace Tuxxedo\Mail;

interface AddressInterface
{
    public string $email {
        get;
    }

    public ?string $displayName {
        get;
    }

    public string $localPart {
        get;
    }

    public string $domain {
        get;
    }

    public function toRfc5322(): string;

    public function isInternationalized(): bool;
}
