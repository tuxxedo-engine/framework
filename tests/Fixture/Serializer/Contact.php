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

namespace Fixture\Serializer;

use Tuxxedo\Serializer\Attribute\CaseStyle;
use Tuxxedo\Text\KeyCase;

#[CaseStyle(KeyCase::SNAKE)]
class Contact
{
    public function __construct(
        public readonly int $id,
        public readonly string $fullName,
        public readonly ?string $phone = null,
        public readonly bool $active = true,
        public readonly float $balance = 0.0,
        public readonly Status $status = Status::ACTIVE,
        public readonly ?Address $homeAddress = null,
    ) {
    }
}
