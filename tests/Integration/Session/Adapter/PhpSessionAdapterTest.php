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

namespace Integration\Session\Adapter;

use Fixture\Session\Color;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\SameSite;
use Tuxxedo\Session\Adapter\PhpSessionAdapter;
use Tuxxedo\Session\Config\SessionConfig;
use Tuxxedo\Session\Config\SessionConfigInterface;
use Tuxxedo\Session\SessionAdapterInterface;
use Tuxxedo\Session\SessionException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class PhpSessionAdapterTest extends TestCase
{
    private function makeConfig(): SessionConfigInterface
    {
        return new SessionConfig(
            lifetime: 3600,
            path: '/',
            domain: '',
            httpOnly: true,
            secure: false,
            sameSite: SameSite::STRICT,
        );
    }

    public function testCreateFromConfigBuildsAdapterFromConfigDirectives(): void
    {
        $adapter = PhpSessionAdapter::createFromConfig(
            config: $this->makeConfig(),
        );

        self::assertFalse($adapter->isStarted());
    }

    public function testIsStartedReturnsFalseBeforeFirstUse(): void
    {
        $adapter = new PhpSessionAdapter();

        self::assertFalse($adapter->isStarted());
    }

    public function testStartTransitionsAdapterToStartedState(): void
    {
        $adapter = new PhpSessionAdapter();

        $result = $adapter->start();

        self::assertTrue($adapter->isStarted());
        self::assertSame($adapter, $result);
    }

    public function testStartIsIdempotentWhenAlreadyStarted(): void
    {
        $adapter = new PhpSessionAdapter();

        $adapter->start();
        $adapter->start();

        self::assertTrue($adapter->isStarted());
    }

    public function testStopMarksAdapterAsNotStarted(): void
    {
        $adapter = new PhpSessionAdapter();

        $adapter->start();

        $result = $adapter->stop();

        self::assertFalse($adapter->isStarted());
        self::assertSame($adapter, $result);
    }

    public function testStopIsNoOpWhenNotStarted(): void
    {
        $adapter = new PhpSessionAdapter();

        $result = $adapter->stop();

        self::assertFalse($adapter->isStarted());
        self::assertSame($adapter, $result);
    }

    public function testRestartStopsAndStartsAgain(): void
    {
        $adapter = new PhpSessionAdapter();

        $adapter->start();
        $adapter->restart();

        self::assertTrue($adapter->isStarted());
    }

    public function testUnsetClearsAllValuesButKeepsSessionStarted(): void
    {
        $adapter = new PhpSessionAdapter();

        $adapter->set('foo', 'bar');
        $adapter->set('baz', 'qux');

        $adapter->unset();

        self::assertTrue($adapter->isStarted());
        self::assertSame([], $adapter->all());
    }

    public function testRegenerateIdentifierProducesNewIdentifierWithoutLosingStartedState(): void
    {
        $adapter = new PhpSessionAdapter();

        $originalIdentifier = $adapter->getIdentifier();

        $result = $adapter->regenerateIdentifier();

        $newIdentifier = $adapter->getIdentifier();

        self::assertSame($adapter, $result);
        self::assertTrue($adapter->isStarted());
        self::assertNotEmpty($newIdentifier);
        self::assertNotSame($originalIdentifier, $newIdentifier);
    }

    public function testDataAccessStartsSessionLazily(): void
    {
        $adapter = new PhpSessionAdapter();

        self::assertFalse($adapter->has('foo'));
        self::assertTrue($adapter->isStarted());
    }

    public function testSetTreatsUnitEnumValueAsItsName(): void
    {
        $adapter = new PhpSessionAdapter();

        $adapter->set('color', Color::RED);

        self::assertSame('RED', $adapter->getRaw('color'));
    }

    public function testDataAccessWorksWithoutManualStart(): void
    {
        $adapter = new PhpSessionAdapter();

        $adapter->set('foo', 'bar');

        self::assertSame('bar', $adapter->getRaw('foo'));
    }

    public function testDataAccessWorksAfterManualStart(): void
    {
        $adapter = new PhpSessionAdapter();

        $adapter->start();

        $adapter->set('foo', 'bar');

        self::assertSame('bar', $adapter->getRaw('foo'));
    }

    public function testSameSiteNoneWithoutSecureThrows(): void
    {
        $this->expectException(SessionException::class);

        new PhpSessionAdapter(
            sameSite: SameSite::NONE,
        );
    }

    public function testSameSiteNoneWithSecureIsAccepted(): void
    {
        $adapter = new PhpSessionAdapter(
            secure: true,
            sameSite: SameSite::NONE,
        );

        self::assertFalse($adapter->isStarted());
    }

    public function testSessionConfigSameSiteNoneWithoutSecureThrows(): void
    {
        $this->expectException(SessionException::class);

        new SessionConfig(
            sameSite: SameSite::NONE,
        );
    }

    public function testIsDefaultImplementation(): void
    {
        $container = new Container();
        $adapter = $container->resolve(SessionAdapterInterface::class);

        self::assertInstanceOf(PhpSessionAdapter::class, $adapter);
        self::assertFalse($adapter->isStarted());
        self::assertSame($adapter, $container->resolve(SessionAdapterInterface::class));
    }
}
