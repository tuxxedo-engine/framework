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
use Tuxxedo\Serializer\Xml\XmlDocumentReader;
use Tuxxedo\Serializer\Xml\XmlDocumentWriter;
use Tuxxedo\Serializer\Xml\XmlNode;
use Tuxxedo\Validator\ValidatorInterface;

class XmlSerializer extends AbstractSerializer implements XmlSerializerInterface
{
    private const string LIST_ROOT = 'list';

    private readonly XmlDocumentReader $reader;
    private readonly XmlDocumentWriter $writer;

    /**
     * @throws SerializerException
     */
    public function __construct(
        ?ValidatorInterface $validator = null,
        bool $skipUnknownKeys = true,
        bool $skipValidation = false,
        public readonly ?string $indent = null,
        ?MetaDataReaderInterface $metaData = null,
    ) {
        parent::__construct(
            validator: $validator,
            skipUnknownKeys: $skipUnknownKeys,
            skipValidation: $skipValidation,
            metaData: $metaData,
        );

        $this->reader = new XmlDocumentReader($this->metaData);
        $this->writer = new XmlDocumentWriter(
            metaData: $this->metaData,
            indent: $indent,
        );
    }

    public function decode(
        string $xml,
        string $className,
        ?string $key = null,
        ?array $only = null,
    ): object {
        $node = self::selectNode(
            node: $this->reader->parse($xml),
            key: $key,
        );

        return $this->hydrateObject(self::only($this->reader->toArray($node, $className), $only), $className);
    }

    public function decodeList(
        string $xml,
        string $className,
        ?string $key = null,
        ?array $only = null,
    ): array {
        $node = self::selectNode(
            node: $this->reader->parse($xml),
            key: $key,
        );

        $data = [];

        foreach ($node->children as $item) {
            $data[] = $this->reader->toArray($item, $className);
        }

        return $this->hydrateList(self::onlyEach($data, $only), $className);
    }

    public function encode(
        object $value,
    ): string {
        $metaData = $this->metaData->read($value::class);

        return $this->writer->document(
            name: $metaData->xmlRoot,
            data: $this->extractObject($value),
            metaData: $metaData,
        );
    }

    public function encodeList(
        iterable $values,
    ): string {
        $items = [];

        foreach ($values as $value) {
            $items[] = [
                $this->extractObject($value),
                $this->metaData->read($value::class),
            ];
        }

        return $this->writer->listDocument(self::LIST_ROOT, $items);
    }

    protected function casting(): Casting
    {
        return Casting::LENIENT;
    }

    private static function selectNode(
        XmlNode $node,
        ?string $key,
    ): XmlNode {
        if ($key === null) {
            return $node;
        }

        foreach ($node->children as $child) {
            if ($child->name === $key) {
                return $child;
            }
        }

        return new XmlNode($key);
    }
}
