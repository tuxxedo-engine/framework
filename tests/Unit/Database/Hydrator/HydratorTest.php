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

namespace Unit\Database\Hydrator;

use Fixture\Database\HydratableTestUser;
use Fixture\Database\Hydrator\HydrationAbstractModel;
use Fixture\Database\Hydrator\HydrationReadonlyModel;
use Fixture\Database\Hydrator\HydrationRegularModel;
use Fixture\Database\Status;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\Hydrator\HydratableInterface;
use Tuxxedo\Database\Hydrator\HydrationException;
use Tuxxedo\Database\Hydrator\Hydrator;

class HydratorTest extends TestCase
{
    private function makeHydrator(): Hydrator
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return new Hydrator(
            container: $container,
        );
    }

    public function testHydrateHydratableInterfaceCallsCreate(): void
    {
        $user = $this->makeHydrator()->hydrate(
            className: HydratableTestUser::class,
            values: [
                'id' => 1,
                'name' => 'Alice',
                'email' => 'alice@example.test',
            ],
        );

        self::assertInstanceOf(
            HydratableTestUser::class,
            $user,
        );

        self::assertSame(
            1,
            $user->id,
        );

        self::assertSame(
            'Alice',
            $user->name,
        );

        self::assertSame(
            'alice@example.test',
            $user->email,
        );
    }

    public function testHydrateReadonlyClassSetsProperties(): void
    {
        $model = $this->makeHydrator()->hydrate(
            className: HydrationReadonlyModel::class,
            values: [
                'id' => 42,
                'name' => 'Alice',
                'email' => null,
            ],
        );

        self::assertInstanceOf(
            HydrationReadonlyModel::class,
            $model,
        );

        self::assertSame(
            42,
            $model->id,
        );

        self::assertSame(
            'Alice',
            $model->name,
        );

        self::assertNull(
            $model->email,
        );
    }

    public function testHydrateRegularClassSplitsConstructorAndLeftoverProperties(): void
    {
        $model = $this->makeHydrator()->hydrate(
            className: HydrationRegularModel::class,
            values: [
                'id' => 7,
                'name' => 'Foo',
            ],
        );

        self::assertInstanceOf(
            HydrationRegularModel::class,
            $model,
        );

        self::assertSame(
            7,
            $model->id,
        );

        self::assertSame(
            'Foo',
            $model->name,
        );
    }

    public function testHydrateThrowsOnAbstractClass(): void
    {
        $this->expectException(HydrationException::class);

        $this->makeHydrator()->hydrate(
            className: HydrationAbstractModel::class,
            values: [],
        );
    }

    public function testHydrateThrowsOnInterface(): void
    {
        $this->expectException(HydrationException::class);

        $this->makeHydrator()->hydrate(
            className: HydratableInterface::class,
            values: [],
        );
    }

    public function testHydrateThrowsOnEnum(): void
    {
        $this->expectException(HydrationException::class);

        $this->makeHydrator()->hydrate(
            className: Status::class,
            values: [],
        );
    }

    public function testHydrateThrowsOnMissingPropertyInReadonly(): void
    {
        $this->expectException(HydrationException::class);

        $this->makeHydrator()->hydrate(
            className: HydrationReadonlyModel::class,
            values: [
                'id' => 1,
                'name' => 'X',
                'email' => null,
                'unknown_column' => 'boom',
            ],
        );
    }

    public function testHydrateThrowsOnMissingPropertyInRegular(): void
    {
        $this->expectException(HydrationException::class);

        $this->makeHydrator()->hydrate(
            className: HydrationRegularModel::class,
            values: [
                'id' => 1,
                'unknown_column' => 'boom',
            ],
        );
    }
}
