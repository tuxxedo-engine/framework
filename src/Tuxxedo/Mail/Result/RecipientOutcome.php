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

class RecipientOutcome
{
    public function __construct(
        public readonly AddressInterface $recipient,
        public readonly RecipientStatus $status,
        public readonly ?int $code = null,
        public readonly ?string $summary = null,
    ) {
    }
}
