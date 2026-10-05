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

namespace Support\View\Lumi\Runtime;

use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;

class StubRuntimeContext implements RuntimeContextInterface
{
    public RuntimeFunctionPolicy $functionPolicy;

    /**
     * @var array<string, string|int|float|bool|null>
     */
    private array $directives;

    /**
     * @var string[]
     */
    private array $blocks;

    /**
     * @param array<string, string|int|float|bool|null> $directives
     * @param string[] $blocks
     */
    public function __construct(
        array $directives = [],
        array $blocks = [],
        RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::ALLOW_ALL,
    ) {
        $this->directives = $directives;
        $this->functionPolicy = $functionPolicy;
        $this->blocks = $blocks;
    }

    public function hasDirective(
        string $directive,
    ): bool {
        return \array_key_exists($directive, $this->directives);
    }

    public function directive(
        string $directive,
    ): string|int|float|bool|null {
        return $this->directives[$directive] ?? null;
    }

    public function hasFilter(
        string $filter,
    ): bool {
        return false;
    }

    public function callFilter(
        mixed $value,
        string $filter,
    ): mixed {
        return $value;
    }

    public function hasFunction(
        string $function,
    ): bool {
        return false;
    }

    public function callFunction(
        string $function,
        array $arguments = [],
    ): mixed {
        return null;
    }

    public function hasBlock(
        string $name,
    ): bool {
        return \in_array($name, $this->blocks, true);
    }
}
