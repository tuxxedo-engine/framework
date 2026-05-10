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

namespace Fixture\View\Lumi\LumiConfigurator;

use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Library\LibraryDiscoveryInterface;

class StubLibraryDiscovery implements LibraryDiscoveryInterface
{
    #[LumiFunction(name: 'stub_fn', aliases: ['stub_fn_alias'])]
    public function stubFunction(): mixed
    {
        return null;
    }

    #[LumiFilter(name: 'stub_filter', aliases: ['stub_filter_alias'])]
    public function stubFilter(): mixed
    {
        return null;
    }

    public function unannotatedMethod(): mixed
    {
        return null;
    }
}
