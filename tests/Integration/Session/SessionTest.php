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
        self::assertSame('bar', $session->getRaw('foo'));
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

    public function testGetRawReturnsStoredValue(): void
    {
        $session = $this->makeSession();
        $session->set('payload', ['nested' => true]);

        self::assertSame(
            [
                'nested' => true,
            ],
            $session->getRaw('payload'),
        );
    }

    public function testGetRawReturnsDefaultForMissingKey(): void
    {
        self::assertSame(
            'fallback',
            $this->makeSession()->getRaw('missing', 'fallback'),
        );
    }

    public function testGetIntReturnsIntegerValue(): void
    {
        $session = $this->makeSession();
        $session->set('count', 42);

        self::assertSame(42, $session->getInt('count'));
    }

    public function testGetIntReturnsDefaultForNonIntegerValue(): void
    {
        $session = $this->makeSession();
        $session->set('count', 'not an int');

        self::assertSame(99, $session->getInt('count', 99));
    }

    public function testGetBoolReturnsBooleanValue(): void
    {
        $session = $this->makeSession();
        $session->set('enabled', true);

        self::assertTrue($session->getBool('enabled'));
    }

    public function testGetBoolReturnsDefaultForNonBooleanValue(): void
    {
        $session = $this->makeSession();
        $session->set('enabled', 1);

        self::assertTrue($session->getBool('enabled', true));
    }

    public function testGetFloatReturnsFloatValue(): void
    {
        $session = $this->makeSession();
        $session->set('ratio', 3.14);

        self::assertSame(3.14, $session->getFloat('ratio'));
    }

    public function testGetFloatReturnsDefaultForNonFloatValue(): void
    {
        $session = $this->makeSession();
        $session->set('ratio', 'three');

        self::assertSame(1.5, $session->getFloat('ratio', 1.5));
    }

    public function testGetStringReturnsStringValue(): void
    {
        $session = $this->makeSession();
        $session->set('name', 'engine');

        self::assertSame('engine', $session->getString('name'));
    }

    public function testGetStringReturnsDefaultForNonStringValue(): void
    {
        $session = $this->makeSession();
        $session->set('name', 42);

        self::assertSame('fallback', $session->getString('name', 'fallback'));
    }

    public function testGetEnumReturnsMatchingCase(): void
    {
        $session = $this->makeSession();
        $session->set('color', Color::GREEN);

        self::assertSame(
            Color::GREEN,
            $session->getEnum('color', Color::class),
        );
    }

    public function testGetEnumThrowsWhenKeyIsMissingFromSession(): void
    {
        self::expectException(SessionException::class);

        $this->makeSession()->getEnum('missing', Color::class);
    }

    public function testGetEnumThrowsWhenStoredValueDoesNotMatchAnyCase(): void
    {
        $session = $this->makeSession();
        $session->set('color', 'PURPLE');

        self::expectException(SessionException::class);

        $session->getEnum('color', Color::class);
    }
}
