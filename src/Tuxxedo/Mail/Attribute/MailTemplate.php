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

namespace Tuxxedo\Mail\Attribute;

use Tuxxedo\Mail\BodyType;

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
class MailTemplate
{
    public function __construct(
        public readonly string $name,
        public readonly BodyType $bodyType = BodyType::HTML,
    ) {
    }

    /**
     * @param object|class-string $target
     */
    public static function extractFrom(
        object|string $target,
    ): ?self {
        $reflector = new \ReflectionClass($target);
        $attributes = $reflector->getAttributes(self::class);

        if ($attributes === []) {
            return null;
        }

        /** @var self */
        return $attributes[0]->newInstance();
    }
}
