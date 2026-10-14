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

namespace Tuxxedo\Validator\Message;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Validator\ViolationInterface;

#[DefaultImplementation(class: TranslatorMessageFormatter::class, lifecycle: Lifecycle::SINGLETON)]
interface MessageFormatterInterface
{
    public function format(
        ViolationInterface $violation,
    ): string;
}
