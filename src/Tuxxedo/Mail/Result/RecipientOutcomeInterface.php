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

namespace Tuxxedo\Mail\Result;

use Tuxxedo\Mail\AddressInterface;

interface RecipientOutcomeInterface
{
    public AddressInterface $recipient {
        get;
    }

    public RecipientStatus $status {
        get;
    }

    public ?int $code {
        get;
    }

    public ?string $summary {
        get;
    }
}
