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

namespace Tuxxedo\View\Lumi\Optimizer;

use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

interface OptimizerResultInterface
{
    public NodeStreamInterface $stream {
        get;
    }

    public bool $changed {
        get;
    }
}
