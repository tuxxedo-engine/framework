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

use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
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
     * @var array<string, FilterInterface>
     */
    private array $filters;

    /**
     * @var array<string, FunctionInterface>
     */
    private array $functions;

    /**
     * @var string[]
     */
    private array $blocks;

    /**
     * @param array<string, string|int|float|bool|null> $directives
     * @param FilterInterface[] $filters
     * @param FunctionInterface[] $functions
     * @param string[] $blocks
     */
    public function __construct(
        array $directives = [],
        array $filters = [],
        array $functions = [],
        array $blocks = [],
        RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::ALLOW_ALL,
    ) {
        $this->directives = $directives;
        $this->functionPolicy = $functionPolicy;
        $this->blocks = $blocks;

        $this->filters = [];

        foreach ($filters as $filter) {
            $this->filters[\strtolower($filter->name)] = $filter;
        }

        $this->functions = [];

        foreach ($functions as $function) {
            $this->functions[\strtolower($function->name)] = $function;
        }
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
        return \array_key_exists(\strtolower($filter), $this->filters);
    }

    public function callFilter(
        mixed $value,
        string $filter,
    ): mixed {
        $instance = $this->filters[\strtolower($filter)] ?? null;

        if ($instance === null) {
            return $value;
        }

        return $instance->call($value, fn (): static => $this);
    }

    public function hasFunction(
        string $function,
    ): bool {
        return \array_key_exists(\strtolower($function), $this->functions);
    }

    public function callFunction(
        string $function,
        array $arguments = [],
    ): mixed {
        $instance = $this->functions[\strtolower($function)] ?? null;

        if ($instance === null) {
            return null;
        }

        return $instance->call($arguments, fn (): static => $this);
    }

    public function hasBlock(
        string $name,
    ): bool {
        return \in_array($name, $this->blocks, true);
    }
}
