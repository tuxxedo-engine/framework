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

namespace Unit\Fixture\Reflection;

#[\Attribute(flags: \Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
class MethodIntrospector
{
    #[SimpleAttribute(value: 'zero')]
    public function one(): void
    {
    }

    #[SimpleAttribute(value: 'one')]
    #[SimpleAttribute(value: 'two')]
    public function two(): void
    {
    }
}
