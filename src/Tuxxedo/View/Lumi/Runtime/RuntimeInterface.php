<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Runtime;

use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewRenderInterface;

interface RuntimeInterface
{
    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $directives {
        get;
    }

    /**
     * @var string[]
     */
    public array $functions {
        get;
    }

    /**
     * @var array<string, \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed>
     */
    public array $customFunctions {
        get;
    }

    public RuntimeFunctionPolicy $functionPolicy {
        get;
    }

    public LumiEngine $lumi {
        get;
    }

    /**
     * @var array<class-string>
     */
    public array $instanceCallClasses {
        get;
    }

    /**
     * @var array<string, \Closure(mixed $value, DirectivesInterface $directives): mixed>
     */
    public array $filters {
        get;
    }

    /**
     * @var array<string, string>
     */
    public array $blocks {
        get;
    }

    public function engine(
        LumiEngine $lumi,
    ): void;

    public function renderer(
        ViewRenderInterface $render,
    ): void;

    /**
     * @param array<string, string|int|float|bool|null>|null $directives
     * @param array<string, string>|null $blocks
     */
    public function pushState(
        ?array $directives = null,
        ?array $blocks = null,
    ): void;

    /**
     * @throws ViewException
     */
    public function popState(): void;

    public function directive(
        string $directive,
        string|int|float|bool|null $value,
    ): void;

    /**
     * @param callable-string $function
     * @param mixed[] $arguments
     *
     * @throws ViewException
     */
    public function functionCall(
        string $function,
        array $arguments = [],
    ): mixed;

    /**
     * @throws ViewException
     */
    public function instanceCall(
        mixed $instance,
        bool $nullSafe = false,
    ): ?object;

    public function hasFilter(
        string $filter,
    ): bool;

    /**
     * @throws ViewException
     */
    public function filter(
        mixed $value,
        string $filter,
    ): mixed;

    /**
     * @throws ViewException
     */
    public function propertyAccess(
        mixed $instance,
        bool $nullSafe = false,
    ): ?object;

    /**
     * @throws ViewException
     */
    public function assertThis(
        mixed $value,
    ): void;

    public function hasBlock(
        string $name,
    ): bool;

    /**
     * @throws ViewException
     */
    public function blockCode(
        string $name,
    ): string;

    public function block(
        string $name,
        string $code,
    ): void;

    public function layout(
        string $file,
    ): void;

    public function highlight(
        string $theme,
        string $sourceCode,
    ): void;
}
