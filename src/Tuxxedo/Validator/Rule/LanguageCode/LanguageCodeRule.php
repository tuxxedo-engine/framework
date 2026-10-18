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

namespace Tuxxedo\Validator\Rule\LanguageCode;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\ValidatorException;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class LanguageCodeRule implements RuleInterface
{
    /**
     * @var list<string>|null
     */
    private ?array $codes = null;

    /**
     * @throws ValidatorException
     */
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

        if (!\in_array(\strtolower($value), $this->codes(), true)) {
            return new Violation(
                code: LanguageCodeViolationCode::NOT_RECOGNIZED,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        return null;
    }

    /**
     * @return list<string>
     *
     * @throws ValidatorException
     */
    private function codes(): array
    {
        if ($this->codes === null) {
            $languages = \ResourceBundle::create('en', 'ICUDATA-lang')?->get('Languages');

            if (!$languages instanceof \ResourceBundle) {
                // @codeCoverageIgnoreStart
                throw ValidatorException::fromUnavailableLanguageData();
                // @codeCoverageIgnoreEnd
            }

            $codes = [];

            foreach ($languages as $code => $name) {
                if (\is_string($code) && \preg_match('/^[a-z]{2}$/', $code) === 1) {
                    $codes[] = $code;
                }
            }

            $this->codes = $codes;
        }

        return $this->codes;
    }
}
