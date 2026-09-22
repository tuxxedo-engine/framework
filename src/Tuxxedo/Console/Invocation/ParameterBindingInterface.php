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

namespace Tuxxedo\Console\Invocation;

use Tuxxedo\Console\Descriptor\ArgumentDescriptorInterface;
use Tuxxedo\Console\Descriptor\FlagDescriptorInterface;
use Tuxxedo\Console\Descriptor\OptionDescriptorInterface;

interface ParameterBindingInterface
{
    /**
     * @var list<ArgumentDescriptorInterface>
     */
    public array $arguments {
        get;
    }

    /**
     * @var list<OptionDescriptorInterface>
     */
    public array $options {
        get;
    }

    /**
     * @var list<FlagDescriptorInterface>
     */
    public array $flags {
        get;
    }
}
