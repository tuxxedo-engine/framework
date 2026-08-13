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

namespace Unit\Mail;

use PHPUnit\Framework\TestCase;
use Support\Mail\Middleware\RecordingMessageMiddleware;
use Support\Mail\Middleware\RecordingWireMiddleware;
use Support\Mail\Serializer\StubMessageSerializer;
use Support\Mail\Transport\RecordingMailTransport;
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Config\MailManagerConfig;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailManager;
use Tuxxedo\Mail\MailManagerConfigurator;
use Tuxxedo\Mail\Serializer\MessageSerializer;
use Tuxxedo\Mail\Transport\FileMail\Config\FileMailTransportConfig;
use Tuxxedo\Mail\Transport\FileMail\FileMailTransport;

class MailManagerConfiguratorTest extends TestCase
{
    public function testConstructorFallsBackToMessageSerializer(): void
    {
        $configurator = new MailManagerConfigurator();

        self::assertInstanceOf(
            MessageSerializer::class,
            $configurator->serializer,
        );
    }

    public function testWithTransportStoresTransportAndReturnsSelf(): void
    {
        $configurator = new MailManagerConfigurator();
        $transport = new RecordingMailTransport();

        $result = $configurator->withTransport($transport);

        self::assertSame($configurator, $result);
        self::assertSame($transport, $configurator->transport);
    }

    public function testWithSerializerReplacesSerializer(): void
    {
        $configurator = new MailManagerConfigurator();
        $serializer = new StubMessageSerializer();

        $result = $configurator->withSerializer($serializer);

        self::assertSame($configurator, $result);
        self::assertSame($serializer, $configurator->serializer);
    }

    public function testWithMessageMiddlewareAppends(): void
    {
        $configurator = new MailManagerConfigurator();
        $first = new RecordingMessageMiddleware();
        $second = new RecordingMessageMiddleware();

        $configurator->withMessageMiddleware($first);
        $configurator->withMessageMiddleware($second);

        self::assertSame(
            [
                $first,
                $second,
            ],
            $configurator->messageMiddleware,
        );
    }

    public function testWithWireMiddlewareAppends(): void
    {
        $configurator = new MailManagerConfigurator();
        $first = new RecordingWireMiddleware();
        $second = new RecordingWireMiddleware();

        $configurator->withWireMiddleware($first);
        $configurator->withWireMiddleware($second);

        self::assertSame(
            [
                $first,
                $second,
            ],
            $configurator->wireMiddleware,
        );
    }

    public function testBuildProducesMailManagerWithConfiguredState(): void
    {
        $transport = new RecordingMailTransport();
        $serializer = new StubMessageSerializer();
        $messageMiddleware = new RecordingMessageMiddleware();
        $wireMiddleware = new RecordingWireMiddleware();

        $manager = (new MailManagerConfigurator())
            ->withTransport($transport)
            ->withSerializer($serializer)
            ->withMessageMiddleware($messageMiddleware)
            ->withWireMiddleware($wireMiddleware)
            ->build();

        self::assertInstanceOf(MailManager::class, $manager);
        self::assertSame($transport, $manager->transport);
    }

    public function testBuildThrowsWhenTransportIsMissing(): void
    {
        $configurator = new MailManagerConfigurator();

        try {
            $configurator->build();

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString(
                'transport',
                \strtolower($exception->getMessage()),
            );
        }
    }

    public function testFromConfigResolvesConfigAndCreatesTransport(): void
    {
        $transportConfig = new FileMailTransportConfig(
            directory: '/tmp',
        );

        $mailConfig = new MailManagerConfig(
            transport: $transportConfig,
        );

        $transport = new FileMailTransport(
            config: $transportConfig,
        );

        $container = new Container();
        $container->singleton($mailConfig);
        $container->singleton($transport);

        $configurator = MailManagerConfigurator::fromConfig($container);

        self::assertSame(
            $transport,
            $configurator->transport,
        );
    }
}
