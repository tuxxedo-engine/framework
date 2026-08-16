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

namespace Tuxxedo\Validator\Rule\In;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class InRule implements RuleInterface
{
    /**
     * @var list<string|int|\BackedEnum>
     */
    public readonly array $values;

    /**
     * @param list<string|int|\BackedEnum> $values
     */
    public function __construct(
        array $values,
    ) {
        $this->values = $values;
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        $normalized = self::normalize($this->values);
        $probe = $value instanceof \BackedEnum ? $value->value : $value;

        if (\in_array($probe, $normalized, true)) {
            return null;
        }

        return new Violation(
            code: InViolationCode::NOT_IN_LIST,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new InViolationContext(
                allowed: $normalized,
            ),
        );
    }

    /**
     * @param list<string|int|\BackedEnum> $values
     * @return list<string|int>
     */
    private static function normalize(
        array $values,
    ): array {
        $normalized = [];

        foreach ($values as $entry) {
            $normalized[] = $entry instanceof \BackedEnum ? $entry->value : $entry;
        }

        return $normalized;
    }
}
