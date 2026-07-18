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

namespace Fixture\Model;

use Tuxxedo\Model\Attribute\Column;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class CustomTinyColumn extends Column
{
    /**
     * @param class-string<CoercerInterface>|null $coercer
     * @param class-string<BehaviorInterface>|null $behavior
     */
    public function __construct(
        public int $precision = 4,
        ?string $name = null,
        ?string $coercer = null,
        ?string $behavior = null,
    ) {
        parent::__construct(
            name: $name,
            coercer: $coercer,
            behavior: $behavior,
        );
    }
}
