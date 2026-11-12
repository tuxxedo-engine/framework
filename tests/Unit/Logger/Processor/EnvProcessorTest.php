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

namespace Unit\Logger\Processor;

use PHPUnit\Framework\TestCase;
use Support\Env\Source\StubEnvSource;
use Tuxxedo\Container\Container;
use Tuxxedo\Env\Env;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Logger\Processor\EnvProcessor;
use Tuxxedo\Temporal\Instant;

class EnvProcessorTest extends TestCase
{
    public function testAddsListedKeysWithNullForUnset(): void
    {
        $container = new Container();
        $container->singleton(
            new Env(
                new StubEnvSource(
                    [
                        'APP_REGION' => 'eu-north-1',
                        'DEPLOY_SHA' => '78c0f17d',
                        'DB_PASSWORD' => 'secret',
                    ],
                ),
            ),
        );

        $processor = new EnvProcessor(
            keys: [
                'APP_REGION',
                'DEPLOY_SHA',
                'K8S_POD_NAME',
            ],
        );

        $record = $processor->process(
            $container,
            new LogRecord(
                message: 'message',
                level: LogLevel::INFO,
                timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
            ),
        );

        self::assertSame(
            [
                'APP_REGION',
                'DEPLOY_SHA',
                'K8S_POD_NAME',
            ],
            $processor->keys,
        );
        self::assertSame(
            [
                'env' => [
                    'APP_REGION' => 'eu-north-1',
                    'DEPLOY_SHA' => '78c0f17d',
                    'K8S_POD_NAME' => null,
                ],
            ],
            $record->extra,
        );
    }
}
