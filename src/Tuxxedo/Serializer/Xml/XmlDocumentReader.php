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

use Tuxxedo\Serializer\MetaData\MetaDataReaderInterface;
use Tuxxedo\Serializer\MetaData\PropertyMetaData;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;

class XmlDocumentReader
{
    private const array TEXT_NODES = [
        \XMLReader::TEXT,
        \XMLReader::CDATA,
        \XMLReader::WHITESPACE,
        \XMLReader::SIGNIFICANT_WHITESPACE,
    ];

    public function __construct(
        private readonly MetaDataReaderInterface $metaData,
    ) {
    }

    /**
     * @throws SerializerInputException
     */
    public function parse(
        string $xml,
    ): XmlNode {
        if (\trim($xml) === '') {
            throw SerializerInputException::fromEmptyXml();
        }

        $useInternalErrors = \libxml_use_internal_errors(true);

        \libxml_clear_errors();

        try {
            $reader = \XMLReader::fromString(
                source: $xml,
                flags: \LIBXML_NONET | \LIBXML_NOCDATA,
            );

            $root = null;
            $stack = [];

            while ($reader->read()) {
                $type = $reader->nodeType;

                if ($type === \XMLReader::DOC_TYPE) {
                    throw SerializerInputException::fromXmlDoctype();
                }

                if ($type === \XMLReader::ELEMENT) {
                    $empty = $reader->isEmptyElement;
                    $node = new XmlNode(
                        name: $reader->localName,
                        attributes: self::attributes($reader),
                    );

                    if (\sizeof($stack) === 0) {
                        $root ??= $node;
                    } else {
                        $stack[\sizeof($stack) - 1]->children[] = $node;
                    }

                    if (!$empty) {
                        $stack[] = $node;
                    }

                    continue;
                }

                if ($type === \XMLReader::END_ELEMENT) {
                    \array_pop($stack);

                    continue;
                }

                if (\sizeof($stack) > 0 && \in_array($type, self::TEXT_NODES, true)) {
                    $stack[\sizeof($stack) - 1]->text .= $reader->value;
                }
            }

            foreach (\libxml_get_errors() as $error) {
                if ($error->level !== \LIBXML_ERR_WARNING) {
                    throw SerializerInputException::fromMalformedXml(\trim($error->message));
                }
            }

            if ($root === null) {
                throw SerializerInputException::fromXmlWithoutRoot(); // @codeCoverageIgnore
            }

            return $root;
        } finally {
            \libxml_clear_errors();
            \libxml_use_internal_errors($useInternalErrors);
        }
    }

    /**
     * @param class-string $className
     * @return array<mixed>
     *
     * @throws SerializerException
     */
    public function toArray(
        XmlNode $node,
        string $className,
    ): array {
        $data = [];
        $usedAttributes = [];
        $usedChildren = [];

        foreach ($this->metaData->read($className)->properties as $property) {
            $key = $property->key;

            if ($property->xmlAttribute) {
                $usedAttributes[$key] = true;
                $value = $node->attributes[$key] ?? '';

                if ($value !== '') {
                    $data[$key] = $value;
                }

                continue;
            }

            if ($property->xmlText) {
                if ($node->text !== '') {
                    $data[$key] = $node->text;
                }

                continue;
            }

            if (self::isList($property)) {
                if ($property->xmlWrapped) {
                    $usedChildren[$key] = true;
                    $wrapper = self::child($node, $key);
                    $items = $wrapper === null
                        ? []
                        : $wrapper->children;
                    $itemName = $property->xmlItem;
                } else {
                    $itemName = $property->xmlItem ?? $key;
                    $usedChildren[$itemName] = true;
                    $items = $node->children;
                }

                if ($itemName !== null) {
                    $items = \array_values(
                        \array_filter(
                            $items,
                            static fn (XmlNode $item): bool => $item->name === $itemName,
                        ),
                    );
                }

                if (\sizeof($items) > 0) {
                    $data[$key] = \array_map(
                        fn (XmlNode $item): mixed => $this->value($item, $property->listOf),
                        $items,
                    );
                }

                continue;
            }

            $usedChildren[$key] = true;
            $child = self::child($node, $key);

            if ($child === null || self::isEmpty($child)) {
                continue;
            }

            if ($property->mapOf !== null) {
                $map = [];

                foreach ($child->children as $entry) {
                    $map[$entry->name] = $this->value($entry, $property->mapOf);
                }

                $data[$key] = $map;

                continue;
            }

            $data[$key] = $this->value(
                node: $child,
                type: $property->converter === null
                    ? $property->typeName
                    : null,
            );
        }

        foreach ($node->attributes as $name => $value) {
            if (!isset($usedAttributes[$name]) && !\array_key_exists($name, $data)) {
                $data[$name] = $value;
            }
        }

        foreach ($node->children as $child) {
            if (!isset($usedChildren[$child->name]) && !\array_key_exists($child->name, $data)) {
                $data[$child->name] = $child->text;
            }
        }

        return $data;
    }

    /**
     * @throws SerializerException
     */
    private function value(
        XmlNode $node,
        ?string $type,
    ): mixed {
        if (
            $type === null ||
            !\class_exists($type) ||
            \is_a($type, \UnitEnum::class, true) ||
            \is_a($type, InstantInterface::class, true) ||
            \is_a($type, LocalDateInterface::class, true) ||
            \is_a($type, LocalTimeInterface::class, true)
        ) {
            return $node->text;
        }

        return $this->toArray($node, $type);
    }

    /**
     * @return array<string, string>
     */
    private static function attributes(
        \XMLReader $reader,
    ): array {
        $attributes = [];

        if (!$reader->moveToFirstAttribute()) {
            return $attributes;
        }

        do {
            if ($reader->prefix !== 'xmlns' && $reader->name !== 'xmlns') {
                $attributes[$reader->localName] = $reader->value;
            }
        } while ($reader->moveToNextAttribute());

        $reader->moveToElement();

        return $attributes;
    }

    private static function child(
        XmlNode $node,
        string $name,
    ): ?XmlNode {
        foreach ($node->children as $child) {
            if ($child->name === $name) {
                return $child;
            }
        }

        return null;
    }

    private static function isEmpty(
        XmlNode $node,
    ): bool {
        return \sizeof($node->children) === 0 &&
            \sizeof($node->attributes) === 0 &&
            \trim($node->text) === '';
    }

    private static function isList(
        PropertyMetaData $property,
    ): bool {
        return $property->listOf !== null ||
            ($property->builtin && $property->typeName === 'array' && $property->mapOf === null);
    }
}
