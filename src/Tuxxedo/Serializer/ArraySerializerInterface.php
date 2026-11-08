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

use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorException;

interface ArraySerializerInterface
{
    /**
     * @template TClassName of object
     *
     * @param array<mixed> $data
     * @param class-string<TClassName> $className
     * @return TClassName
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function fromArray(
        array $data,
        string $className,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param array<mixed> $data
     * @param class-string<TClassName> $className
     * @return list<TClassName>
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function fromArrayList(
        array $data,
        string $className,
    ): array;

    /**
     * @return array<string, mixed>
     *
     * @throws SerializerException
     */
    public function toArray(
        object $value,
    ): array;

    /**
     * @param iterable<object> $values
     * @return list<array<string, mixed>>
     *
     * @throws SerializerException
     */
    public function toArrayList(
        iterable $values,
    ): array;
}
