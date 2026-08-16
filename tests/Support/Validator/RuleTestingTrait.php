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

namespace Support\Validator;

use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContext;
use Tuxxedo\Validator\ViolationInterface;

trait RuleTestingTrait
{
    private function runRule(
        RuleInterface $rule,
        mixed $value,
        string $path = 'field',
        ?ContainerInterface $container = null,
    ): ?ViolationInterface {
        return $rule->check(
            value: $value,
            context: new ValidationContext(
                currentPath: $path,
            ),
            container: $container ?? new Container(),
        );
    }
}
