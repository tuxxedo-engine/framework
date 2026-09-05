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

namespace Tuxxedo\Model\Aggregate;

use Tuxxedo\Model\MetaData\ModelMetaDataInterface;

readonly class CollectedEntity
{
    public function __construct(
        public string $path,
        public object $entity,
        public ModelMetaDataInterface $metaData,
    ) {
    }
}
