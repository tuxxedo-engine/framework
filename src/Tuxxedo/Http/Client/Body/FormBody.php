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

namespace Tuxxedo\Http\Client\Body;

use Tuxxedo\Serializer\SerializerManagerInterface;

readonly class FormBody implements RequestBodyInterface
{
    public string $contentType;

    /**
     * @param array<mixed>|object $fields
     */
    public function __construct(
        public array|object $fields,
        public bool $bracketArrays = false,
    ) {
        $this->contentType = 'application/x-www-form-urlencoded';
    }

    public function encode(
        SerializerManagerInterface $serializerManager,
    ): string {
        $fields = \is_object($this->fields)
            ? $serializerManager->array()->toArray($this->fields)
            : $this->fields;

        $pairs = [];

        foreach ($fields as $name => $value) {
            $this->append(
                pairs: $pairs,
                name: (string) $name,
                value: $value,
            );
        }

        return \join('&', $pairs);
    }

    /**
     * @param list<string> $pairs
     */
    private function append(
        array &$pairs,
        string $name,
        mixed $value,
    ): void {
        if ($value === null) {
            return;
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                $this->append(
                    pairs: $pairs,
                    name: match (true) {
                        !\array_is_list($value) || \is_array($item) => $name . '[' . $key . ']',
                        $this->bracketArrays => $name . '[]',
                        default => $name,
                    },
                    value: $item,
                );
            }

            return;
        }

        $pairs[] = \urlencode($name) . '=' . \urlencode(self::scalar($value));
    }

    private static function scalar(
        mixed $value,
    ): string {
        return match (true) {
            $value === true => '1',
            $value === false => '0',
            \is_scalar($value) => (string) $value,
            $value instanceof \BackedEnum => (string) $value->value,
            $value instanceof \UnitEnum => $value->name,
            $value instanceof \Stringable => (string) $value,
            default => '',
        };
    }
}
