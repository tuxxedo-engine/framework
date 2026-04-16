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

namespace Tuxxedo\Router;

// @todo Consider prefix defaults so that actions do not have to declare optional prefixes, perhaps this is
//       better done via a second interface that extends this. The defaults likely needs to be in the form of a
//       method, e.g. getDefaultValue(string $argument): mixed. If this idea makes it into the code, then there
//       is also a need to have a way to retrieve these defaults even if an argument was not declared at the
//       action level
interface PrefixInterface
{
    public string $uri {
        get;
    }
}
