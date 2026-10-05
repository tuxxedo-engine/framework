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

namespace Tuxxedo\View\Lumi\Runtime\Introspector;

interface CallableMetadataInterface
{
    public string $name {
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

    public bool $wantsContext {
        get;
    }

    public ?int $contextParameterIndex {
        get;
    }

    /**
     * @var list<string>
     */
    public array $aliases {
        get;
    }
}
