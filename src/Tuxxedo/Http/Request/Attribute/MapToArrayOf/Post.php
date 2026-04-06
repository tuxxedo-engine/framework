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

namespace Tuxxedo\Http\Request\Attribute\MapToArrayOf;

use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\Attribute\AbstractMapToArrayOf;

#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
// @todo Consider changing the namespace
class Post extends AbstractMapToArrayOf
{
    protected InputContext $context = InputContext::POST;
}
