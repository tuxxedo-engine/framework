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

namespace Tuxxedo\Http\Client\Body;

use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\SerializerManagerInterface;

interface RequestBodyInterface
{
    public string $contentType {
        get;
    }

    /**
     * @throws SerializerException
     */
    public function encode(
        SerializerManagerInterface $serializerManager,
    ): StreamInterface|string;
}
