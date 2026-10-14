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

namespace Tuxxedo\Validator\Message;

use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Validator\ViolationInterface;

class TranslatorMessageFormatter implements MessageFormatterInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function format(
        ViolationInterface $violation,
    ): string {
        $key = (string) $violation->code->value;
        $translated = $this->translator->translate(
            key: $key,
            replacements: self::buildPlaceholders(
                violation: $violation,
            ),
        );

        if ($translated !== $key || $this->translator->has(key: $key)) {
            return $translated;
        }

        return $this->translator->translate(
            key: 'validator.fallback',
            replacements: [
                'path' => $violation->propertyPath,
                'code' => $key,
            ],
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    private static function buildPlaceholders(
        ViolationInterface $violation,
    ): array {
        $placeholders = [
            'path' => $violation->propertyPath,
            'value' => self::stringifyValue(
                value: $violation->invalidValue,
            ),
        ];

        if ($violation->context !== null) {
            foreach (\get_object_vars($violation->context) as $key => $value) {
                $placeholders[$key] = self::stringifyValue(
                    value: $value,
                );
            }
        }

        return $placeholders;
    }

    private static function stringifyValue(
        mixed $value,
    ): string {
        if ($value === null) {
            return 'null';
        }

        if (\is_bool($value)) {
            return $value
                ? 'true'
                : 'false';
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (\is_scalar($value)) {
            return (string) $value;
        }

        if (\is_array($value)) {
            return \join(', ', \array_map(self::stringifyValue(...), $value));
        }

        return \get_debug_type($value);
    }
}
