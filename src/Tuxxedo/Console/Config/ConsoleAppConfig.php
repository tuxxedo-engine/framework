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

namespace Tuxxedo\Console\Config;

use Tuxxedo\Application\Profile;
use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('console')]
class ConsoleAppConfig implements ConsoleAppConfigInterface
{
    public readonly SuggestionPolicy $suggestionPolicy;
    public readonly HelpConfig $helpConfig;

    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly Profile $profile,
        ?SuggestionPolicy $suggestionPolicy = null,
        ?HelpConfig $helpConfig = null,
    ) {
        $this->suggestionPolicy = $suggestionPolicy ?? SuggestionPolicy::default();
        $this->helpConfig = $helpConfig ?? HelpConfig::default();
    }
}
