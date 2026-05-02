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

namespace Unit\Debug;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Debug\DebugErrorHandler;

class DebugErrorHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        /** @var bool $value */
        $value = (new \ReflectionProperty(DebugErrorHandler::class, 'registeredPhpErrorHandler'))->getValue();

        if ($value) {
            DebugErrorHandler::restorePhpErrorHandler();
        }
    }

    public function testConstructorRegistersErrorHandlerByDefault(): void
    {
        $handler = new DebugErrorHandler();

        self::expectException(\ErrorException::class);

        \trigger_error('test error', \E_USER_WARNING);
    }

    public function testConstructorSkipsRegistration(): void
    {
        $handler = new DebugErrorHandler(registerPhpErrorHandler: false);

        $prop = new \ReflectionProperty(DebugErrorHandler::class, 'registeredPhpErrorHandler');

        self::assertFalse($prop->getValue());
    }

    public function testRegisterPhpErrorHandlerConvertsErrorToException(): void
    {
        DebugErrorHandler::registerPhpErrorHandler();

        self::expectException(\ErrorException::class);

        \trigger_error('test error', \E_USER_WARNING);
    }

    public function testRegisterPhpErrorHandlerSetsStaticFlag(): void
    {
        DebugErrorHandler::registerPhpErrorHandler();

        $prop = new \ReflectionProperty(DebugErrorHandler::class, 'registeredPhpErrorHandler');

        self::assertTrue($prop->getValue());
    }

    public function testRestorePhpErrorHandlerClearsStaticFlag(): void
    {
        DebugErrorHandler::registerPhpErrorHandler();
        DebugErrorHandler::restorePhpErrorHandler();

        $prop = new \ReflectionProperty(DebugErrorHandler::class, 'registeredPhpErrorHandler');

        self::assertFalse($prop->getValue());
    }

    public function testDestructorRestoresHandlerWhenRegistered(): void
    {
        $handler = new DebugErrorHandler();

        unset($handler);

        $prop = new \ReflectionProperty(DebugErrorHandler::class, 'registeredPhpErrorHandler');

        self::assertFalse($prop->getValue());
    }

    public function testDestructorDoesNothingWhenNotRegistered(): void
    {
        $handler = new DebugErrorHandler(registerPhpErrorHandler: false);

        unset($handler);

        $prop = new \ReflectionProperty(DebugErrorHandler::class, 'registeredPhpErrorHandler');

        self::assertFalse($prop->getValue());
    }
}
