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

interface ModelMetaDataInterface
{
    /**
     * @var class-string
     */
    public string $model {
        get;
    }

    public string $table {
        get;
    }

    public ModelPrimaryKeyInterface|ModelCompositeKeyInterface|null $key {
        get;
    }

    /**
     * @var non-empty-array<ModelColumnInterface>
     */
    public array $columns {
        get;
    }

    /**
     * @var ModelIdentifierInterface[]
     */
    public array $identifiers {
        get;
    }
}
