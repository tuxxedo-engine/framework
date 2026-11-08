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

interface ValueConverterInterface
{
    /**
     * @throws SerializerException
     */
    public function decode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed;

    /**
     * @throws SerializerException
     */
    public function encode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed;
}
