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

namespace Tuxxedo\Console\Descriptor;

interface ArgumentDescriptorInterface
{
    public string $name {
        get;
    }

    public int $position {
        get;
    }

    public ?string $description {
        get;
    }

    public string $typeName {
        get;
    }

    public bool $isBuiltin {
        get;
    }

    public bool $isNullable {
        get;
    }

    public bool $hasDefault {
        get;
    }

    public mixed $default {
        get;
    }

    public bool $isVariadic {
        get;
    }
}
