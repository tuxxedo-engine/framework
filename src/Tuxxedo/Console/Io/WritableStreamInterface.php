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

namespace Tuxxedo\Console\Io;

interface WritableStreamInterface
{
    public function write(string $buffer): int;
    public function writeLine(string $buffer): int;

    // @todo Implement ask()
    // @todo Implement question()
    // @todo Implement confirm()
    // @todo Implement colors
}
