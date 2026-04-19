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
class PropertyIntrospector
{
    #[SimpleAttribute(value: 'zero')]
    public private(set) string $one = 'one';

    #[SimpleAttribute(value: 'one')]
    #[SimpleAttribute(value: 'two')]
    public private(set) ?string $two = 'two';

    public function __construct(
        public readonly DefaultTypeInterfaceA $a = new DefaultType(),
        public readonly ?DefaultTypeInterfaceA $b = null,
        public readonly DefaultTypeInterfaceA|DefaultTypeInterfaceB $c = new DefaultType(),
        public readonly (DefaultType&DefaultTypeInterfaceB)|DefaultTypeInterfaceA $d = new DefaultType(),
        public readonly (DefaultTypeInterfaceA&DefaultTypeInterfaceB)|DefaultType|null $e = null,
    ) {
    }
}
