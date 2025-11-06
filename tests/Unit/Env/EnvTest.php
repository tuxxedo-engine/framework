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

namespace Unit\Env;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Env\Env;
use Tuxxedo\Env\EnvException;
use Tuxxedo\Env\EnvLoaderInterface;

class EnvTest extends TestCase
{
    public function testCreateFromEnv(): void
    {
        $env = Env::createFromEnvironment();

        self::assertTrue($env->has('PATH'));
        self::assertFalse($env->has('|æøå'));

        $this->expectException(EnvException::class);
        $env->getString('|æøå');
    }

    public function testGetters(): void
    {
        $env = new Env(
            loader: new class () implements EnvLoaderInterface {
                /**
                 * @var array<string, string>
                 */
                private array $variables = [
                    'boolOn' => 'on',
                    'boolOff' => 'off',
                    'boolYes' => 'yes',
                    'boolNo' => 'no',
                    'boolTrue' => 'true',
                    'boolFalse' => 'false',
                    'boolIntTrue' => '1',
                    'boolIntFalse' => '0',
                    'string' => 'foo',
                    'int' => '42',
                    'float' => '13.37',
                ];

                public function has(string $variable): bool
                {
                    return \array_key_exists($variable, $this->variables);
                }

                public function value(string $variable): string
                {
                    if (!\array_key_exists($variable, $this->variables)) {
                        throw EnvException::fromInvalidVariable(
                            variable: $variable,
                        );
                    }

                    return $this->variables[$variable];
                }
            },
        );

        self::assertTrue($env->getBool('boolOn'));
        self::assertFalse($env->getBool('boolOff'));
        self::assertTrue($env->getBool('boolYes'));
        self::assertFalse($env->getBool('boolNo'));
        self::assertTrue($env->getBool('boolTrue'));
        self::assertFalse($env->getBool('boolFalse'));
        self::assertTrue($env->getBool('boolIntTrue'));
        self::assertFalse($env->getBool('boolIntFalse'));
        self::assertSame($env->getString('string'), 'foo');
        self::assertSame($env->getInt('int'), 42);
        self::assertSame($env->getFloat('float'), 13.37);
    }
}
