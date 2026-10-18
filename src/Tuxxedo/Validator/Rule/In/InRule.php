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
use Tuxxedo\Intl\Normalize;
use Tuxxedo\Validator\CommonViolationCode;
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
        public readonly Normalize $normalize = Normalize::CANONICAL,
    ) {
        $this->values = $values;
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        $allowed = self::unwrap($this->values);
        $probe = $value instanceof \BackedEnum ? $value->value : $value;
        $comparable = $allowed;

        if (\is_string($probe)) {
            $probe = $this->normalize->apply($probe);
            $comparable = [];

            foreach ($allowed as $entry) {
                $comparable[] = \is_string($entry)
                    ? $this->normalize->apply($entry)
                    : $entry;
            }

            if ($probe === null || \in_array(null, $comparable, true)) {
                return new Violation(
                    code: CommonViolationCode::INVALID_ENCODING,
                    propertyPath: $context->currentPath,
                    invalidValue: $value,
                );
            }
        }

        if (\in_array($probe, $comparable, true)) {
            return null;
        }

        return new Violation(
            code: InViolationCode::NOT_IN_LIST,
            propertyPath: $context->currentPath,
            invalidValue: $value,
            context: new InViolationContext(
                allowed: $allowed,
            ),
        );
    }

    /**
     * @param list<string|int|\BackedEnum> $values
     * @return list<string|int>
     */
    private static function unwrap(
        array $values,
    ): array {
        $unwrapped = [];

        foreach ($values as $entry) {
            $unwrapped[] = $entry instanceof \BackedEnum ? $entry->value : $entry;
        }

        return $unwrapped;
    }
}
