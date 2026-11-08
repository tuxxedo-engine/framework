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

class ArraySerializer extends AbstractSerializer implements ArraySerializerInterface
{
    public function fromArray(
        array $data,
        string $className,
    ): object {
        return $this->hydrateObject($data, $className);
    }

    public function fromArrayList(
        array $data,
        string $className,
    ): array {
        if (!\array_is_list($data)) {
            throw SerializerException::fromExpectedList('array');
        }

        return $this->hydrateList($data, $className);
    }

    public function toArray(
        object $value,
    ): array {
        return $this->extractObject($value);
    }

    public function toArrayList(
        iterable $values,
    ): array {
        return $this->extractList($values);
    }

    protected function casting(): Casting
    {
        return Casting::LENIENT;
    }
}
