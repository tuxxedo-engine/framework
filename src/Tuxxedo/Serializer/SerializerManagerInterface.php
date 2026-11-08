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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: SerializerManager::class, lifecycle: Lifecycle::SINGLETON)]
interface SerializerManagerInterface
{
    /**
     * @throws SerializerException
     */
    public function array(
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): ArraySerializerInterface;

    /**
     * @param int<1, max> $depth
     *
     * @throws SerializerException
     */
    public function json(
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        int $flags = 0,
        int $depth = 512,
    ): JsonSerializerInterface;

    /**
     * @throws SerializerException
     */
    public function csv(
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        string $separator = ',',
        string $enclosure = '"',
    ): CsvSerializerInterface;
}
