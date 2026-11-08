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

namespace Support\Serializer;

use Tuxxedo\Container\Container;
use Tuxxedo\Serializer\SerializerManager;
use Tuxxedo\Serializer\SerializerManagerInterface;
use Tuxxedo\Validator\Validator;

class SerializerManagerFactory
{
    public static function create(): SerializerManagerInterface
    {
        return new SerializerManager(new Validator(new Container()));
    }
}
