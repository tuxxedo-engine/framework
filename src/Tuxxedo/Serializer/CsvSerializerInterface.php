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

interface CsvSerializerInterface
{
    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @return list<TClassName>
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function decode(
        string $csv,
        string $className,
    ): array;

    /**
     * @param iterable<object> $values
     *
     * @throws SerializerException
     */
    public function encode(
        iterable $values,
    ): string;
}
