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

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Config;
use Tuxxedo\Http\SameSite;
use Tuxxedo\Session\Adapter\PhpSessionAdapter;
use Tuxxedo\Session\SessionException;
use Tuxxedo\Session\SessionStartMode;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class PhpSessionAdapterTest extends TestCase
{
    private function makeConfig(): Config
    {
        return new Config(
            directives: [
                'session' => [
                    'lifetime' => 3600,
                    'path' => '/',
                    'domain' => '',
                    'httpOnly' => true,
                    'secure' => false,
                    'sameSite' => SameSite::STRICT,
                ],
            ],
        );
    }

    public function testCreateFromConfigBuildsAdapterFromConfigDirectives(): void
    {
        $adapter = PhpSessionAdapter::createFromConfig(
            startMode: SessionStartMode::LAZY,
            config: $this->makeConfig(),
        );

        self::assertSame(SessionStartMode::LAZY, $adapter->startMode);
        self::assertFalse($adapter->isStarted());
    }

    public function testAutoStartModeStartsSessionInConstructor(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::AUTO,
        );

        self::assertTrue($adapter->isStarted());
    }

    public function testIsStartedReturnsFalseBeforeStartInExplicitMode(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::EXPLICIT,
        );

        self::assertFalse($adapter->isStarted());
    }

    public function testStartTransitionsAdapterToStartedState(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::EXPLICIT,
        );

        $result = $adapter->start();

        self::assertTrue($adapter->isStarted());
        self::assertSame($adapter, $result);
    }

    public function testStartIsIdempotentWhenAlreadyStarted(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::AUTO,
        );

        $adapter->start();

        self::assertTrue($adapter->isStarted());
    }

    public function testStopMarksAdapterAsNotStarted(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::AUTO,
        );

        $result = $adapter->stop();

        self::assertFalse($adapter->isStarted());
        self::assertSame($adapter, $result);
    }

    public function testStopIsNoOpWhenNotStarted(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::EXPLICIT,
        );

        $result = $adapter->stop();

        self::assertFalse($adapter->isStarted());
        self::assertSame($adapter, $result);
    }

    public function testRestartStopsAndStartsAgain(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::AUTO,
        );

        $adapter->restart();

        self::assertTrue($adapter->isStarted());
    }

    public function testUnsetClearsAllValuesButKeepsSessionStarted(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::LAZY,
        );

        $adapter->set('foo', 'bar');
        $adapter->set('baz', 'qux');

        $adapter->unset();

        self::assertTrue($adapter->isStarted());
        self::assertSame([], $adapter->all());
    }

    public function testRegenerateIdentifierProducesNewIdentifierWithoutLosingStartedState(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::LAZY,
        );

        $originalIdentifier = $adapter->getIdentifier();

        $result = $adapter->regenerateIdentifier();

        $newIdentifier = $adapter->getIdentifier();

        self::assertSame($adapter, $result);
        self::assertTrue($adapter->isStarted());
        self::assertNotEmpty($newIdentifier);
        self::assertNotSame($originalIdentifier, $newIdentifier);
    }

    public function testExplicitModeThrowsWhenAccessingBeforeStart(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::EXPLICIT,
        );

        self::expectException(SessionException::class);

        $adapter->has('foo');
    }

    public function testSetTreatsUnitEnumValueAsItsName(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::LAZY,
        );

        $adapter->set('mode', SessionStartMode::LAZY);

        self::assertSame('LAZY', $adapter->getRaw('mode'));
    }

    public function testAutoModeAllowsDataAccessWithoutManualStart(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::AUTO,
        );

        $adapter->set('foo', 'bar');

        self::assertSame('bar', $adapter->getRaw('foo'));
    }

    public function testExplicitModeAllowsDataAccessAfterManualStart(): void
    {
        $adapter = new PhpSessionAdapter(
            startMode: SessionStartMode::EXPLICIT,
        );

        $adapter->start();

        $adapter->set('foo', 'bar');

        self::assertSame('bar', $adapter->getRaw('foo'));
    }
}
