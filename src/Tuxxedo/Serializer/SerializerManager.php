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

use Tuxxedo\Serializer\MetaData\MetaDataReader;
use Tuxxedo\Serializer\MetaData\MetaDataReaderInterface;
use Tuxxedo\Validator\ValidatorInterface;

class SerializerManager implements SerializerManagerInterface
{
    private readonly MetaDataReaderInterface $metaData;

    public function __construct(
        private readonly ValidatorInterface $validator,
        ?MetaDataReaderInterface $metaData = null,
    ) {
        $this->metaData = $metaData ?? new MetaDataReader();
    }

    public function array(
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
    ): ArraySerializerInterface {
        return new ArraySerializer(
            validator: $this->validator,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            metaData: $this->metaData,
        );
    }

    /**
     * @param int<1, max> $depth
     */
    public function json(
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        int $flags = 0,
        int $depth = 512,
    ): JsonSerializerInterface {
        return new JsonSerializer(
            validator: $this->validator,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            flags: $flags,
            depth: $depth,
            metaData: $this->metaData,
        );
    }

    public function csv(
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        string $separator = ',',
        string $enclosure = '"',
    ): CsvSerializerInterface {
        return new CsvSerializer(
            validator: $this->validator,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            separator: $separator,
            enclosure: $enclosure,
            metaData: $this->metaData,
        );
    }
}
