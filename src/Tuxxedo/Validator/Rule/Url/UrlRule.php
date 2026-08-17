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

namespace Tuxxedo\Validator\Rule\Url;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class UrlRule implements RuleInterface
{
    /**
     * @param list<string> $allowedSchemes
     */
    public function __construct(
        public readonly array $allowedSchemes = [
            'http',
            'https',
        ],
    ) {
    }

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            return new Violation(
                code: CommonViolationCode::WRONG_TYPE,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new WrongTypeViolationContext(
                    expected: 'string',
                    received: \get_debug_type($value),
                ),
            );
        }

        if (\filter_var($value, \FILTER_VALIDATE_URL) === false) {
            return new Violation(
                code: UrlViolationCode::INVALID_FORMAT,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        $scheme = \parse_url($value, \PHP_URL_SCHEME);

        if (!\is_string($scheme)) {
            $scheme = ''; // @codeCoverageIgnore
        }

        if (!\in_array($scheme, $this->allowedSchemes, true)) {
            return new Violation(
                code: UrlViolationCode::DISALLOWED_SCHEME,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new UrlViolationContext(
                    scheme: $scheme,
                    allowed: $this->allowedSchemes,
                ),
            );
        }

        return null;
    }
}
