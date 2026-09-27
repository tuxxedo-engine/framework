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

namespace Fixture\Console\Invocation;

use Tuxxedo\Console\Attribute\Argument;
use Tuxxedo\Console\Attribute\Flag;
use Tuxxedo\Console\Attribute\Option;

class BinderMethodFixtures
{
    public function stringArgument(
        #[Argument]
        string $value,
    ): void {
    }

    public function intArgument(
        #[Argument]
        int $value,
    ): void {
    }

    public function floatArgument(
        #[Argument]
        float $value,
    ): void {
    }

    public function boolArgument(
        #[Argument]
        bool $value,
    ): void {
    }

    /**
     * @param mixed[] $value
     */
    public function arrayArgument(
        #[Argument]
        array $value,
    ): void {
    }

    public function unsupportedClassArgument(
        #[Argument]
        \stdClass $value,
    ): void {
    }

    public function stringEnumArgument(
        #[Argument]
        BinderStringEnum $value,
    ): void {
    }

    public function intEnumArgument(
        #[Argument]
        BinderIntEnum $value,
    ): void {
    }

    public function unitEnumArgument(
        #[Argument]
        BinderUnitEnum $value,
    ): void {
    }

    public function defaultArgument(
        #[Argument]
        string $value = 'fallback',
    ): void {
    }

    public function variadicArgument(
        #[Argument]
        string ...$values,
    ): void {
    }

    /**
     * @param mixed $value
     */
    public function untypedArgument(
        #[Argument]
        $value,
    ): void {
    }

    public function requiredOption(
        #[Option]
        int $count,
    ): void {
    }

    public function optionalOption(
        #[Option]
        int $count = 1,
    ): void {
    }

    public function repeatableStringOption(
        #[Option(repeatable: true)]
        string $tag,
    ): void {
    }

    public function aliasedOption(
        #[Option(name: 'aliased')]
        int $rawParam,
    ): void {
    }

    public function flag(
        #[Flag]
        bool $force,
    ): void {
    }

    public function classFromContainer(
        BinderService $service,
    ): void {
    }

    public function builtinFromContainer(
        int $value,
    ): void {
    }

    /**
     * @param mixed $value
     */
    public function untypedFromContainer(
        $value,
    ): void {
    }
}
