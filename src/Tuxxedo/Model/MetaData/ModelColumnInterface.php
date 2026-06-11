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

namespace Tuxxedo\Model\MetaData;

use Tuxxedo\Model\Attribute\ColumnInterface;

// @todo Surface ?CoercerInterface $coercer { get; } once coercer system lands — instance built once at metadata-build time via Container::resolve() with the attribute's args, stored on ModelColumn
interface ModelColumnInterface
{
    public string $property {
        get;
    }

    public string $column {
        get;
    }

    public bool $nullable {
        get;
    }

    public bool $unique {
        get;
    }

    public bool $readonly {
        get;
    }

    public ColumnInterface $attribute {
        get;
    }

    public ?ModelPrimaryKeyInterface $primaryKey {
        get;
    }

    public ?ModelIdentifierInterface $identifier {
        get;
    }
}
