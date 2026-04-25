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
use Tuxxedo\Model\Attribute\Identifier;

interface ModelColumnInterface
{
    public string $name {
        get;
    }

    public ColumnInterface $meta {
        get;
    }

    public ?ModelPrimaryKeyInterface $primaryKey {
        get;
    }

    public ?Identifier $identifier {
        get;
    }

    // @todo Relations
    // @todo Nullability detection
}
