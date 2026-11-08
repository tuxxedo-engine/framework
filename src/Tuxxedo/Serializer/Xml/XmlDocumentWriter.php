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

namespace Tuxxedo\Serializer\Xml;

use Tuxxedo\Serializer\MetaData\ClassMetaData;
use Tuxxedo\Serializer\MetaData\MetaDataReaderInterface;
use Tuxxedo\Serializer\MetaData\PropertyMetaData;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;

class XmlDocumentWriter
{
    private const string PROLOG = '<?xml version="1.0" encoding="UTF-8"?>';

    public function __construct(
        private readonly MetaDataReaderInterface $metaData,
        private readonly ?string $indent = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws SerializerException
     */
    public function document(
        string $name,
        array $data,
        ClassMetaData $metaData,
    ): string {
        return self::PROLOG . $this->line(0) . $this->element(
            name: $name,
            data: $data,
            metaData: $metaData,
            depth: 0,
        );
    }

    /**
     * @param list<array{array<string, mixed>, ClassMetaData}> $items
     *
     * @throws SerializerException
     */
    public function listDocument(
        string $name,
        array $items,
    ): string {
        $content = '';

        foreach ($items as [$data, $metaData]) {
            $content .= $this->line(1) . $this->element(
                name: $metaData->xmlRoot,
                data: $data,
                metaData: $metaData,
                depth: 1,
            );
        }

        return self::PROLOG . $this->line(0) . $this->tag(
            name: $name,
            attributes: '',
            content: $content,
            depth: 0,
            nested: $content !== '',
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @throws SerializerException
     */
    private function element(
        string $name,
        array $data,
        ClassMetaData $metaData,
        int $depth,
    ): string {
        $attributes = '';
        $text = '';
        $children = '';

        foreach ($metaData->properties as $property) {
            if (!$property->readable || $property->ignore || !\array_key_exists($property->key, $data)) {
                continue;
            }

            $value = $data[$property->key];

            if ($value === null) {
                continue;
            }

            if ($property->xmlAttribute) {
                $attributes .= ' ' . self::name($property->key) . '="' . self::escape(self::scalar($value, $metaData, $property)) . '"';

                continue;
            }

            if ($property->xmlText) {
                $text .= self::escape(self::scalar($value, $metaData, $property));

                continue;
            }

            $children .= $this->property(
                property: $property,
                value: $value,
                metaData: $metaData,
                depth: $depth + 1,
            );
        }

        return $this->tag(
            name: $name,
            attributes: $attributes,
            content: $text . $children,
            depth: $depth,
            nested: $children !== '',
        );
    }

    /**
     * @throws SerializerException
     */
    private function property(
        PropertyMetaData $property,
        mixed $value,
        ClassMetaData $metaData,
        int $depth,
    ): string {
        if (!\is_array($value)) {
            return $this->line($depth) . $this->tag(
                name: $property->key,
                attributes: '',
                content: self::escape(self::scalar($value, $metaData, $property)),
                depth: $depth,
                nested: false,
            );
        }

        if (
            $property->listOf !== null ||
            ($property->builtin && $property->typeName === 'array' && $property->mapOf === null)
        ) {
            $itemName = $property->xmlWrapped
                ? $property->xmlItem ?? 'item'
                : $property->xmlItem ?? $property->key;

            $itemDepth = $property->xmlWrapped
                ? $depth + 1
                : $depth;

            $items = '';

            foreach ($value as $item) {
                $items .= $this->line($itemDepth) . $this->value(
                    name: $itemName,
                    value: $item,
                    type: $property->listOf,
                    metaData: $metaData,
                    property: $property,
                    depth: $itemDepth,
                );
            }

            if (!$property->xmlWrapped) {
                return $items;
            }

            return $this->line($depth) . $this->tag(
                name: $property->key,
                attributes: '',
                content: $items,
                depth: $depth,
                nested: $items !== '',
            );
        }

        if ($property->mapOf !== null) {
            $entries = '';

            foreach ($value as $entryName => $entry) {
                $entries .= $this->line($depth + 1) . $this->value(
                    name: (string) $entryName,
                    value: $entry,
                    type: $property->mapOf,
                    metaData: $metaData,
                    property: $property,
                    depth: $depth + 1,
                );
            }

            return $this->line($depth) . $this->tag(
                name: $property->key,
                attributes: '',
                content: $entries,
                depth: $depth,
                nested: $entries !== '',
            );
        }

        return $this->line($depth) . $this->value(
            name: $property->key,
            value: $value,
            type: $property->converter === null
                ? $property->typeName
                : null,
            metaData: $metaData,
            property: $property,
            depth: $depth,
        );
    }

    /**
     * @throws SerializerException
     */
    private function value(
        string $name,
        mixed $value,
        ?string $type,
        ClassMetaData $metaData,
        PropertyMetaData $property,
        int $depth,
    ): string {
        if (!\is_array($value)) {
            return $this->tag(
                name: $name,
                attributes: '',
                content: self::escape(self::scalar($value, $metaData, $property)),
                depth: $depth,
                nested: false,
            );
        }

        if (
            $type === null ||
            !\class_exists($type) ||
            \is_a($type, \UnitEnum::class, true) ||
            \is_a($type, InstantInterface::class, true) ||
            \is_a($type, LocalDateInterface::class, true) ||
            \is_a($type, LocalTimeInterface::class, true)
        ) {
            throw SerializerException::fromXmlUnsupportedValue(
                className: $metaData->reflection->name,
                property: $property->name,
            );
        }

        return $this->element(
            name: $name,
            data: $value,
            metaData: $this->metaData->read($type),
            depth: $depth,
        );
    }

    /**
     * @throws SerializerException
     */
    private function tag(
        string $name,
        string $attributes,
        string $content,
        int $depth,
        bool $nested,
    ): string {
        $name = self::name($name);

        if ($content === '') {
            return '<' . $name . $attributes . '/>';
        }

        return '<' . $name . $attributes . '>' .
            $content .
            ($nested ? $this->line($depth) : '') .
            '</' . $name . '>';
    }

    private function line(
        int $depth,
    ): string {
        if ($this->indent === null) {
            return '';
        }

        return "\n" . \str_repeat($this->indent, $depth);
    }

    /**
     * @throws SerializerException
     */
    private static function name(
        string $name,
    ): string {
        if (\preg_match('/^[\p{L}_][\p{L}\p{N}._-]*$/u', $name) !== 1) {
            throw SerializerException::fromXmlName($name);
        }

        return $name;
    }

    /**
     * @throws SerializerException
     */
    private static function escape(
        string $value,
    ): string {
        if (\preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value) === 1) {
            throw SerializerException::fromXmlCharacter();
        }

        return \htmlspecialchars($value, \ENT_XML1 | \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @throws SerializerException
     */
    private static function scalar(
        mixed $value,
        ClassMetaData $metaData,
        PropertyMetaData $property,
    ): string {
        return match (true) {
            $value === true => 'true',
            $value === false => 'false',
            \is_int($value), \is_float($value), \is_string($value) => (string) $value,
            default => throw SerializerException::fromXmlUnsupportedValue(
                className: $metaData->reflection->name,
                property: $property->name,
            ),
        };
    }
}
