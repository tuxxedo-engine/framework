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

use Tuxxedo\View\Lumi\Library\Filter\FilterProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\LibraryProviderInterface;

class StubLibraryProvider implements LibraryProviderInterface
{
    public function __construct(
        private readonly ?FilterProviderInterface $filterProvider = null,
        private readonly ?FunctionProviderInterface $functionProvider = null,
    ) {
    }

    public function filters(): ?FilterProviderInterface
    {
        return $this->filterProvider;
    }

    public function functions(): ?FunctionProviderInterface
    {
        return $this->functionProvider;
    }
}
