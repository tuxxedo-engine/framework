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

use Tuxxedo\Serializer\MetaData\MetaDataReaderInterface;
use Tuxxedo\Validator\ValidatorInterface;

class JsonSerializer extends AbstractSerializer implements JsonSerializerInterface
{
    private const string FORMAT = 'JSON';

    /**
     * @param int<1, max> $depth
     *
     * @throws SerializerException
     */
    public function __construct(
        ?ValidatorInterface $validator = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        public readonly int $flags = 0,
        public readonly int $depth = 512,
        ?MetaDataReaderInterface $metaData = null,
    ) {
        parent::__construct(
            validator: $validator,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            metaData: $metaData,
        );
    }

    public function decode(
        string $json,
        string $className,
    ): object {
        $data = $this->decodeJson($json);

        if (!\is_array($data) || ($data !== [] && \array_is_list($data))) {
            throw SerializerException::fromExpectedObject(self::FORMAT);
        }

        return $this->hydrateObject($data, $className);
    }

    public function decodeList(
        string $json,
        string $className,
    ): array {
        $data = $this->decodeJson($json);

        if (!\is_array($data) || !\array_is_list($data)) {
            throw SerializerException::fromExpectedList(self::FORMAT);
        }

        return $this->hydrateList($data, $className);
    }

    public function encode(
        object $value,
    ): string {
        $data = $this->extractObject($value);

        if ($data === []) {
            return '{}';
        }

        return $this->encodeJson($data);
    }

    public function encodeList(
        iterable $values,
    ): string {
        return $this->encodeJson($this->extractList($values));
    }

    protected function casting(): Casting
    {
        return Casting::STRICT;
    }

    /**
     * @throws SerializerException
     */
    private function decodeJson(
        string $json,
    ): mixed {
        try {
            return \json_decode(
                json: $json,
                associative: true,
                depth: $this->depth,
                flags: $this->flags | \JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $exception) {
            throw SerializerException::fromMalformedInput(
                format: self::FORMAT,
                previous: $exception,
            );
        }
    }

    /**
     * @param array<mixed> $data
     *
     * @throws SerializerException
     */
    private function encodeJson(
        array $data,
    ): string {
        try {
            return \json_encode(
                value: $data,
                flags: $this->flags | \JSON_THROW_ON_ERROR,
                depth: $this->depth,
            );
        } catch (\JsonException $exception) {
            throw SerializerException::fromUnencodableValue(
                format: self::FORMAT,
                previous: $exception,
            );
        }
    }
}
