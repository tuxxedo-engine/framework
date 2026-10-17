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

namespace Tuxxedo\Intl;

interface LocaleInterface
{
    public string $tag {
        get;
    }

    /**
     * @var non-empty-string
     */
    public string $language {
        get;
    }

    /**
     * @var non-empty-string|null
     */
    public ?string $script {
        get;
    }

    /**
     * @var non-empty-string|null
     */
    public ?string $region {
        get;
    }

    public string $languageName {
        get;
    }

    public ?string $scriptName {
        get;
    }

    public ?string $regionName {
        get;
    }

    public string $displayName {
        get;
    }

    public LocaleDirection $direction {
        get;
    }

    /**
     * @return iterable<int, LocaleInterface>
     */
    public function fallbackChain(): iterable;
}
