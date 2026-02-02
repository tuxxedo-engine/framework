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

namespace Tuxxedo\View\Lumi\Runtime\Library;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\View\Lumi\Runtime\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Runtime\Function\PhpFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\DirectiveFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\HasDirectiveFunction;

class StandardFunctions implements FunctionProviderInterface
{
    public function export(
        ContainerInterface $container,
    ): \Generator {
        yield new HasDirectiveFunction();
        yield new DirectiveFunction();

        yield new PhpFunction(
            name: 'count',
            aliases: [
                'sizeof',
            ],
        );
    }
}
