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

namespace Tuxxedo\Collection;

class CollectionException extends \Exception
{
    public static function fromWriteViolation(): self
    {
        return new self(
            message: 'Immutable collections cannot be modified',
        );
    }
}
