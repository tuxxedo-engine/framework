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

namespace Tuxxedo\Validator;

interface ViolationInterface
{
    public ViolationCodeInterface&\BackedEnum $code {
        get;
    }

    public string $propertyPath {
        get;
    }

    public mixed $invalidValue {
        get;
    }

    public ?ViolationContextInterface $context {
        get;
    }
}
