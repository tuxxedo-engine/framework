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

namespace Tuxxedo\Database\Query\Statement\Table;

interface ColumnDescriptionInterface
{
    public string $name {
        get;
    }

    // @todo Hydrate this raw driver string into an Engine-native column type attribute
    public string $nativeType {
        get;
    }

    public bool $nullable {
        get;
    }

    public ?string $default {
        get;
    }

    public bool $primary {
        get;
    }

    public bool $autoIncrement {
        get;
    }
}
