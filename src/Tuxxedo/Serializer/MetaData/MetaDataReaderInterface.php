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

namespace Tuxxedo\Serializer\MetaData;

use Tuxxedo\Serializer\SerializerException;

interface MetaDataReaderInterface
{
    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    public function read(
        string $className,
    ): ClassMetaData;
}
