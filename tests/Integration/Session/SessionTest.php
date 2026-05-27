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

namespace Integration\Session;

use Fixture\Session\Color;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Session\Adapter\PhpSessionAdapter;
use Tuxxedo\Session\Session;
use Tuxxedo\Session\SessionException;
use Tuxxedo\Session\SessionStartMode;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SessionTest extends TestCase
{
    private function makeSession(): Session
    {
        return new Session(
            adapter: new PhpSessionAdapter(
                startMode: SessionStartMode::LAZY,
            ),
        );
    }

    public function testGetIdentifierReturnsNonEmptyStringAfterLazyStart(): void
    {
        $identifier = $this->makeSession()->getIdentifier();

        self::assertNotEmpty($identifier);
    }

    public function testSetStoresValueAndReturnsSelf(): void
    {
        $session = $this->makeSession();

        $result = $session->set('foo', 'bar');

        self::assertSame($session, $result);
        self::assertSame('bar', $session->raw('foo'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $session = $this->makeSession();
        $session->set('flag', true);

        self::assertTrue($session->has('flag'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        self::assertFalse($this->makeSession()->has('missing'));
    }

    public function testRemoveDeletesKey(): void
    {
        $session = $this->makeSession();
        $session->set('flag', true);

        $session->remove('flag');

        self::assertFalse($session->has('flag'));
    }

    public function testAllReturnsAllStoredValues(): void
    {
        $session = $this->makeSession();
        $session->set('a', 1);
        $session->set('b', 'two');

        self::assertSame(
            [
                'a' => 1,
                'b' => 'two',
            ],
            $session->all(),
        );
    }

    public function testRawReturnsStoredValue(): void
    {
        $session = $this->makeSession();
        $session->set('payload', ['nested' => true]);

        self::assertSame(
            [
                'nested' => true,
            ],
            $session->raw('payload'),
        );
    }

    public function testRawReturnsDefaultForMissingKey(): void
    {
        self::assertSame(
            'fallback',
            $this->makeSession()->raw('missing', 'fallback'),
        );
    }

    public function testIntReturnsIntegerValue(): void
    {
        $session = $this->makeSession();
        $session->set('count', 42);

        self::assertSame(42, $session->int('count'));
    }

    public function testIntReturnsDefaultForNonIntegerValue(): void
    {
        $session = $this->makeSession();
        $session->set('count', 'not an int');

        self::assertSame(99, $session->int('count', 99));
    }

    public function testBoolReturnsBooleanValue(): void
    {
        $session = $this->makeSession();
        $session->set('enabled', true);

        self::assertTrue($session->bool('enabled'));
    }

    public function testBoolReturnsDefaultForNonBooleanValue(): void
    {
        $session = $this->makeSession();
        $session->set('enabled', 1);

        self::assertTrue($session->bool('enabled', true));
    }

    public function testFloatReturnsFloatValue(): void
    {
        $session = $this->makeSession();
        $session->set('ratio', 3.14);

        self::assertSame(3.14, $session->float('ratio'));
    }

    public function testFloatReturnsDefaultForNonFloatValue(): void
    {
        $session = $this->makeSession();
        $session->set('ratio', 'three');

        self::assertSame(1.5, $session->float('ratio', 1.5));
    }

    public function testStringReturnsStringValue(): void
    {
        $session = $this->makeSession();
        $session->set('name', 'engine');

        self::assertSame('engine', $session->string('name'));
    }

    public function testStringReturnsDefaultForNonStringValue(): void
    {
        $session = $this->makeSession();
        $session->set('name', 42);

        self::assertSame('fallback', $session->string('name', 'fallback'));
    }

    public function testEnumReturnsMatchingCase(): void
    {
        $session = $this->makeSession();
        $session->set('color', Color::GREEN);

        self::assertSame(
            Color::GREEN,
            $session->enum('color', Color::class),
        );
    }

    public function testEnumThrowsWhenKeyIsMissingFromSession(): void
    {
        self::expectException(SessionException::class);

        $this->makeSession()->enum('missing', Color::class);
    }

    public function testEnumThrowsWhenStoredValueDoesNotMatchAnyCase(): void
    {
        $session = $this->makeSession();
        $session->set('color', 'PURPLE');

        self::expectException(SessionException::class);

        $session->enum('color', Color::class);
    }
}
