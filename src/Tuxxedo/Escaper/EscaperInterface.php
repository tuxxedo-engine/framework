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

namespace Tuxxedo\Escaper;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: Escaper::class, lifecycle: Lifecycle::PERSISTENT)]
interface EscaperInterface
{
    public function html(
        string $input,
    ): string;

    public function htmlComment(
        string $input,
    ): string;

    public function attribute(
        string $input,
    ): string;

    public function js(
        string $input,
    ): string;

    public function url(
        string $input,
    ): string;

    public function css(
        string $input,
    ): string;
}
