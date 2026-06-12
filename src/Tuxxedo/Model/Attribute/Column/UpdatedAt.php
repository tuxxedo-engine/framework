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

namespace Tuxxedo\Model\Attribute\Column;

use Tuxxedo\Model\Behavior\UpdatedAtBehavior;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\Hydrator\Coercer\DateTimeCoercer;

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
readonly class UpdatedAt extends DateTime
{
    /**
     * @param class-string<CoercerInterface>|null $coercer
     */
    public function __construct(
        DateFormat|string $format = DateFormat::DEFAULT,
        ?string $name = null,
        ?string $coercer = DateTimeCoercer::class,
    ) {
        parent::__construct(
            format: $format,
            name: $name,
            coercer: $coercer,
            behavior: UpdatedAtBehavior::class,
        );
    }
}
