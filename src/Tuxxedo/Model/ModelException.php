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

namespace Tuxxedo\Model;

class ModelException extends \Exception
{
    public static function fromInvalidModelClass(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s"',
                $modelClass,
            ),
        );
    }
}
