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

namespace Unit\Mapper;

use Fixtures\Mapper\Person;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Mapper\Mapper;
use Tuxxedo\Mapper\MapperException;

class MapperTest extends TestCase
{
    /**
     * @return \Generator<array<class-string|object|\Closure(): object>>
     */
    public static function mapperDtoDataProvider(): \Generator
    {
        yield [
            Person::class,
        ];

        yield [
            static fn (): Person => new Person(),
        ];

        yield [
            new Person(),
        ];
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     */
    #[DataProvider('mapperDtoDataProvider')]
    public function testMapToArrayOf(string|object $className): void
    {
        $people = [
            [
                'firstName' => 'Rasmus',
                'lastName' => 'Lerdorf',
            ],
            [
                'firstName' => 'Mr.',
                'lastName' => 'Bean',
            ],
            [
                'firstName' => 'Morgan',
                'lastName' => 'Freeman',
            ],
            new Person(
                firstName: 'Linus',
                lastName: 'Torvalds',
            ),
        ];

        $i = 0;
        $mappedPeople = (new Mapper())->mapToArrayOf($people, $className);

        foreach ($mappedPeople as $person) {
            self::assertInstanceOf(Person::class, $person);

            if (\is_array($people[$i])) {
                $firstName = $people[$i]['firstName'];
                $lastName = $people[$i]['lastName'];
            } elseif ($people[$i] instanceof Person) {
                $firstName = $people[$i]->firstName;
                $lastName = $people[$i]->lastName;
            }

            self::assertSame($firstName, $person->firstName);
            self::assertSame($lastName, $person->lastName);

            $i++;
        }
    }

    public function testMapToArrayOfIterativeTypeError(): void
    {
        $this->expectException(MapperException::class);
        (new Mapper())->mapToArrayOf(
            input: [
                'string',
            ],
            className: Person::class,
        );
    }

    public function testMapToArrayOfPropertyTypeError(): void
    {
        $this->expectException(MapperException::class);
        (new Mapper())->mapArrayTo(
            input: [
                'firstName' => new Person(),
                'lastName' => 'Foo',
            ],
            className: Person::class,
        );
    }

    public function testMapToArrayOfUnknownPropertyError(): void
    {
        $this->expectException(MapperException::class);
        (new Mapper())->mapArrayTo(
            input: [
                'firstName' => 'Bjarne',
                'surName' => 'Stroustrup',
            ],
            className: Person::class,
        );
    }
}
