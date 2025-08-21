<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Token;

readonly class TypeToken implements TokenInterface
{
    public string $type;

    public function __construct(
        public string $op1,
        public string $op2,
    ) {
        $this->type = BuiltinTokenNames::TYPE->name;
    }
}
