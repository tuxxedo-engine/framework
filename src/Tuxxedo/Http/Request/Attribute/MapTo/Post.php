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

namespace Tuxxedo\Http\Request\Attribute\MapTo;

use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\Attribute\AbstractMapTo;

#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Post extends AbstractMapTo
{
    protected InputContext $context = InputContext::POST;
}
