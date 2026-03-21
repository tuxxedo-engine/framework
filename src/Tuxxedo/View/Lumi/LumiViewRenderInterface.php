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

namespace Tuxxedo\View\Lumi;

use Tuxxedo\View\Lumi\Runtime\LoaderInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeInterface;
use Tuxxedo\View\ViewRenderInterface;

interface LumiViewRenderInterface extends ViewRenderInterface
{
    public LoaderInterface $loader {
        get;
    }

    public RuntimeInterface $runtime {
        get;
    }

    public bool $alwaysCompile {
        get;
    }
}
