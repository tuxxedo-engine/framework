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

namespace Tuxxedo\View\Lumi\Runtime;

use Tuxxedo\View\ViewException;

readonly class RuntimeContext implements RuntimeContextInterface
{
    public RuntimeFunctionPolicy $functionPolicy;

    public function __construct(
        private RuntimeInterface $runtime,
    ) {
        $this->functionPolicy = $this->runtime->functionPolicy;
    }

    public function hasDirective(
        string $directive,
    ): bool {
        return \array_key_exists($directive, $this->runtime->directives);
    }

    public function directive(
        string $directive,
    ): string|int|float|bool|null {
        if (!\array_key_exists($directive, $this->runtime->directives)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        }

        return $this->runtime->directives[$directive];
    }

    public function hasFilter(
        string $filter,
    ): bool {
        return $this->runtime->hasFilter($filter);
    }

    public function callFilter(
        mixed $value,
        string $filter,
    ): mixed {
        return $this->runtime->filter($value, $filter);
    }

    public function hasFunction(
        string $function,
    ): bool {
        return \array_key_exists(\strtolower($function), $this->runtime->functions);
    }

    /**
     * @param mixed[] $arguments
     */
    public function callFunction(
        string $function,
        array $arguments = [],
    ): mixed {
        return $this->runtime->functionCall($function, $arguments);
    }

    public function hasBlock(
        string $name,
    ): bool {
        return $this->runtime->hasBlock($name);
    }
}
