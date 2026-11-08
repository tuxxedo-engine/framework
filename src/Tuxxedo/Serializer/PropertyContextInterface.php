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

namespace Tuxxedo\Serializer;

interface PropertyContextInterface
{
    /**
     * @var class-string
     */
    public string $className {
        get;
    }

    public string $propertyName {
        get;
    }

    public string $path {
        get;
    }

    public ?string $typeName {
        get;
    }

    public bool $nullable {
        get;
    }

    public Casting $casting {
        get;
    }
}
