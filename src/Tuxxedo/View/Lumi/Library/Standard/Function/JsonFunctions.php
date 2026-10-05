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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class JsonFunctions
{
    #[LumiFunction('json')]
    public function json(
        mixed $value,
    ): string {
        return \json_encode($value, \JSON_THROW_ON_ERROR);
    }

    #[LumiFunction('jsonPretty')]
    public function jsonPretty(
        mixed $value,
    ): string {
        return \json_encode($value, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
    }
}
