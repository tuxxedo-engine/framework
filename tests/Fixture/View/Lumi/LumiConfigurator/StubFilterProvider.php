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

use Fixture\View\Lumi\Runtime\RecordingFilter;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\View\Lumi\Library\Filter\FilterProviderInterface;

class StubFilterProvider implements FilterProviderInterface
{
    public function export(
        ContainerInterface $container,
    ): \Generator {
        yield new RecordingFilter(
            name: 'stub_exported_filter',
        );
    }
}
