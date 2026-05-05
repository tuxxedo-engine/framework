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

namespace Fixture\View\Lumi\Parser\Parser;

use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

readonly class BarNode implements NodeInterface
{
    /**
     * @var array{}
     */
    public array $scopes;

    public function __construct()
    {
        $this->scopes = [];
    }
}
