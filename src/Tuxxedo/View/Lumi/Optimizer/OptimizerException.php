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

namespace Tuxxedo\View\Lumi\Optimizer;

use Tuxxedo\View\Lumi\LumiException;

class OptimizerException extends LumiException
{
    public static function fromCannotPopOptimizerScope(): self
    {
        return new self(
            message: 'Cannot pop optimizer scope, possible optimizer corruption',
        );
    }
}
