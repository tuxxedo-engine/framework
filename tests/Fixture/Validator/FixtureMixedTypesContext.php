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

namespace Fixture\Validator;

use Tuxxedo\Validator\ViolationContextInterface;

class FixtureMixedTypesContext implements ViolationContextInterface
{
    /**
     * @param list<string> $list
     */
    public function __construct(
        public readonly ?string $nullish,
        public readonly bool $flag,
        public readonly array $list,
        public readonly \stdClass $obj,
    ) {
    }
}
