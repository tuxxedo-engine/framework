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

namespace Tuxxedo\Console\Descriptor;

interface CommandDescriptorInterface
{
    /**
     * @var list<string>
     */
    public array $path {
        get;
    }

    public ?string $description {
        get;
    }

    public bool $hasReturnValue {
        get;
    }

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

    /**
     * @var class-string
     */
    public string $className {
        get;
    }

    public string $methodName {
        get;
    }
}
