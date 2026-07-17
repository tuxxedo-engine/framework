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

namespace Support\Model;

use Tuxxedo\Model\MetaData\Adapter\MetaDataAdapterInterface;
use Tuxxedo\Model\MetaData\Adapter\ReflectionMetaDataAdapter;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;

class CountingMetaDataAdapter implements MetaDataAdapterInterface
{
    public int $calls = 0;

    private readonly ReflectionMetaDataAdapter $delegate;

    public function __construct()
    {
        $this->delegate = new ReflectionMetaDataAdapter();
    }

    public function getModel(
        string $model,
    ): ModelMetaDataInterface {
        $this->calls++;

        return $this->delegate->getModel($model);
    }
}
