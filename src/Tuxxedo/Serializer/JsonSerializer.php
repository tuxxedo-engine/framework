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
        ?string $key = null,
        ?array $only = null,
    ): object {
        $data = $this->decodeJson($json);

        if (!\is_array($data)) {
            throw SerializerInputException::fromExpectedObject(self::FORMAT);
        }

        $data = self::select(
            data: $data,
            key: $key,
            format: self::FORMAT,
        );

        if ($data !== [] && \array_is_list($data)) {
            throw SerializerInputException::fromExpectedObject(self::FORMAT);
        }

        return $this->hydrateObject(self::only($data, $only), $className);
    }

    public function decodeList(
        string $json,
        string $className,
        ?string $key = null,
        ?array $only = null,
    ): array {
        $data = $this->decodeJson($json);

        if (!\is_array($data)) {
            throw SerializerInputException::fromExpectedList(self::FORMAT);
        }

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

    public function encodeValue(
        mixed $value,
    ): string {
        return $this->encodeJson($this->extractValue($value));
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
            throw SerializerInputException::fromMalformedInput(
                format: self::FORMAT,
                previous: $exception,
            );
        }
    }

    /**
     * @throws SerializerException
     */
    private function encodeJson(
        mixed $data,
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
