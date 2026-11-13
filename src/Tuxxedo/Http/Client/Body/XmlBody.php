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

use Tuxxedo\Serializer\SerializerManagerInterface;

readonly class XmlBody implements RequestBodyInterface
{
    public function __construct(
        public object $value,
        public string $contentType = 'application/xml',
    ) {
    }

    public function encode(
        SerializerManagerInterface $serializerManager,
    ): string {
        return $serializerManager->xml()->encode($this->value);
    }
}
