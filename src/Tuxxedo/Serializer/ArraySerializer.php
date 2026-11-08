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
    private const string FORMAT = 'array';

    public function fromArray(
        array $data,
        string $className,
        ?string $key = null,
        ?array $only = null,
    ): object {
        $data = self::select(
            data: $data,
            key: $key,
            format: self::FORMAT,
        );

        return $this->hydrateObject(self::only($data, $only), $className);
    }

    public function fromArrayList(
        array $data,
        string $className,
        ?string $key = null,
        ?array $only = null,
    ): array {
        $data = self::select(
            data: $data,
            key: $key,
            format: self::FORMAT,
        );

        if (!\array_is_list($data)) {
            throw SerializerInputException::fromExpectedList(self::FORMAT);
        }

        return $this->hydrateList(self::onlyEach($data, $only), $className);
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
