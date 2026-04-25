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
use Tuxxedo\Model\Attribute\Relation\RelationInterface;

interface ModelColumnInterface
{
    public string $name {
        get;
    }

    public bool $nullable {
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

    /**
     * @var RelationInterface[]
     */
    public array $relations {
        get;
    }
}
