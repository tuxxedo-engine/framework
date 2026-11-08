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

namespace Unit\Serializer;

use Fixture\Serializer\AbstractTarget;
use Fixture\Serializer\Address;
use Fixture\Serializer\BadConverter;
use Fixture\Serializer\ClassicConstructor;
use Fixture\Serializer\Customer;
use Fixture\Serializer\Level;
use Fixture\Serializer\Money;
use Fixture\Serializer\Node;
use Fixture\Serializer\Order;
use Fixture\Serializer\Plain;
use Fixture\Serializer\Priority;
use Fixture\Serializer\Range;
use Fixture\Serializer\Schedule;
use Fixture\Serializer\Status;
use Fixture\Serializer\ThrowingConstructor;
use Fixture\Serializer\Validated;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Serializer\ArraySerializer;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\Validator;

class ArraySerializerTest extends TestCase
{
    public function testFromArrayCastsStringInputLeniently(): void
    {
        $customer = $this->serializer()->fromArray(
            [
                'id' => '7',
                'first_name' => 'Ada',
                'e-mail' => 'ada@example.test',
                'active' => 'false',
                'balance' => '12.5',
                'status' => 'blocked',
                'priority' => '2',
                'level' => 'EXPERT',
                'billing_address' => [
                    'street' => 'Main 1',
                    'city' => 'Prague',
                ],
                'addresses' => [
                    [
                        'street' => 'Side 2',
                        'city' => 5,
                    ],
                ],
                'tag_ids' => [
                    '1',
                    '2',
                ],
                'prices' => [
                    'base' => '9.5',
                ],
            ],
            Customer::class,
        );

        self::assertSame(7, $customer->id);
        self::assertSame('Ada', $customer->firstName);
        self::assertNull($customer->nickname);
        self::assertSame('ada@example.test', $customer->email);
        self::assertFalse($customer->active);
        self::assertSame(12.5, $customer->balance);
        self::assertSame(Status::BLOCKED, $customer->status);
        self::assertSame(Priority::HIGH, $customer->priority);
        self::assertSame(Level::EXPERT, $customer->level);
        self::assertEquals(new Address('Main 1', 'Prague'), $customer->billingAddress);
        self::assertEquals(
            [
                new Address('Side 2', '5'),
            ],
            $customer->addresses,
        );

        self::assertSame(
            [
                1,
                2,
            ],
            $customer->tagIds,
        );

        self::assertSame(
            [
                'base' => 9.5,
            ],
            $customer->prices,
        );

        self::assertSame('hidden', $customer->secret);
    }

    public function testFromArrayKeepsDefaultsForMissingKeys(): void
    {
        $customer = $this->serializer()->fromArray(
            [
                'id' => 1,
                'first_name' => 'Bob',
                'nickname' => 'bobby',
                'e-mail' => 'bob@example.test',
            ],
            Customer::class,
        );

        self::assertSame('bobby', $customer->nickname);
        self::assertTrue($customer->active);
        self::assertSame(Status::ACTIVE, $customer->status);
        self::assertNull($customer->billingAddress);
        self::assertSame([], $customer->addresses);
    }

    public function testFromArrayCollectsEveryViolationWithPaths(): void
    {
        try {
            $this->serializer()->fromArray(
                [
                    'id' => 'abc',
                    'e-mail' => null,
                    'balance' => 'x',
                    'status' => 'nope',
                    'priority' => 'x',
                    'level' => 5,
                    'billing_address' => [
                        'street' => 'Only street',
                    ],
                    'addresses' => 'not a list',
                    'tag_ids' => [
                        'x',
                        null,
                    ],
                    'prices' => 'not a map',
                ],
                Customer::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    'id' => 'validator.common.wrong-type',
                    'firstName' => 'serializer.missing-key',
                    'email' => 'validator.common.null-value',
                    'balance' => 'validator.common.wrong-type',
                    'status' => 'serializer.invalid-value',
                    'priority' => 'serializer.invalid-value',
                    'level' => 'serializer.invalid-value',
                    'billingAddress.city' => 'serializer.missing-key',
                    'addresses' => 'validator.common.wrong-type',
                    'tagIds.0' => 'validator.common.wrong-type',
                    'tagIds.1' => 'validator.common.null-value',
                    'prices' => 'validator.common.wrong-type',
                ],
                self::violations($exception),
            );
        }
    }

    public function testUnknownKeysAreSkippedByDefault(): void
    {
        $address = $this->serializer()->fromArray(
            [
                'street' => 'Main 1',
                'city' => 'Prague',
                'zip' => '11000',
            ],
            Address::class,
        );

        self::assertSame('Prague', $address->city);
    }

    public function testUnknownKeysAreRejectedWhenNotSkipped(): void
    {
        try {
            $this->serializer(skipUnknownKeys: false)->fromArray(
                [
                    'id' => 1,
                    'first_name' => 'Ada',
                    'e-mail' => 'ada@example.test',
                    'secret' => 'ignored, so known',
                    'zip' => '11000',
                    'billing_address' => [
                        'street' => 'Main 1',
                        'city' => 'Prague',
                        'floor' => 3,
                    ],
                ],
                Customer::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    'billingAddress.floor' => 'serializer.unknown-key',
                    'zip' => 'serializer.unknown-key',
                ],
                self::violations($exception),
            );
        }
    }

    public function testPlainObjectsWithoutConstructor(): void
    {
        $plain = $this->serializer()->fromArray(
            [
                'count' => '3',
                'anything' => [
                    'free' => 'form',
                ],
                'identifier' => 'abc',
                'options' => [
                    'a' => 1,
                ],
                'child' => [
                    'count' => '1',
                    'uninitialized' => 'set',
                ],
                'flag' => 'on',
                'text' => 5,
                'untyped' => 1.5,
                'uninitialized' => 'set',
            ],
            Plain::class,
        );

        self::assertSame(3, $plain->count);
        self::assertNull($plain->label);
        self::assertSame(
            [
                'free' => 'form',
            ],
            $plain->anything,
        );

        self::assertSame('abc', $plain->identifier);
        self::assertInstanceOf(Plain::class, $plain->child);
        self::assertSame(1, $plain->child->count);
        self::assertTrue($plain->flag);
        self::assertSame('5', $plain->text);
        self::assertSame(1.5, $plain->untyped);
    }

    public function testPlainObjectsReportBuiltinTypeMismatches(): void
    {
        try {
            $this->serializer()->fromArray(
                [
                    'options' => 'x',
                    'payload' => [
                        1,
                    ],
                    'countable' => [
                        1,
                    ],
                    'flag' => 'maybe',
                    'text' => [],
                    'child' => 'x',
                ],
                Plain::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    'options' => 'validator.common.wrong-type',
                    'payload' => 'validator.common.wrong-type',
                    'countable' => 'validator.common.wrong-type',
                    'child' => 'validator.common.wrong-type',
                    'flag' => 'validator.common.wrong-type',
                    'text' => 'validator.common.wrong-type',
                    'uninitialized' => 'serializer.missing-key',
                ],
                self::violations($exception),
            );
        }
    }

    public function testExistingInstancesPassThrough(): void
    {
        $address = new Address('Main 1', 'Prague');

        $customer = $this->serializer()->fromArray(
            [
                'id' => 1,
                'first_name' => 'Ada',
                'e-mail' => 'ada@example.test',
                'billing_address' => $address,
            ],
            Customer::class,
        );

        self::assertSame($address, $customer->billingAddress);
    }

    public function testClassicConstructorParameters(): void
    {
        $object = $this->serializer()->fromArray(
            [
                'name' => 'Ada',
                'suffix' => '?',
            ],
            ClassicConstructor::class,
        );

        self::assertSame('Ada', $object->name);
        self::assertSame('Ada?', $object->suffixed);
        self::assertSame(
            [
                'name' => 'Ada',
                'suffixed' => 'Ada?',
            ],
            $this->serializer()->toArray($object),
        );
    }

    public function testConvertersDecodeAndEncode(): void
    {
        $order = $this->serializer()->fromArray(
            [
                'total' => '12.50 EUR',
                'paid' => 'Y',
            ],
            Order::class,
        );

        self::assertEquals(new Money(1250, 'EUR'), $order->total);
        self::assertTrue($order->paid);
        self::assertSame(
            [
                'total' => '12.50 EUR',
                'paid' => 'Y',
            ],
            $this->serializer()->toArray($order),
        );
    }

    public function testConverterFailuresBecomeViolations(): void
    {
        try {
            $this->serializer()->fromArray(
                [
                    'total' => '12 EUR',
                    'paid' => 'maybe',
                ],
                Order::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    'total' => 'serializer.invalid-value',
                    'paid' => 'serializer.invalid-value',
                ],
                self::violations($exception),
            );
        }
    }

    public function testTemporalValuesRoundTrip(): void
    {
        $serializer = $this->serializer();
        $schedule = $serializer->fromArray(
            [
                'at' => '2026-11-08T10:00:00+00:00',
                'day' => '2026-11-08',
                'opensAt' => '09:00:00',
                'closesAt' => '17:30',
                'published' => '08.11.2026',
                'createdAt' => '2026-11-08 10:15',
            ],
            Schedule::class,
        );

        self::assertSame(Instant::parse('2026-11-08T10:00:00+00:00')->toIso8601(), $schedule->at->toIso8601());
        self::assertSame('2026-11-08', $schedule->day->toIso8601());
        self::assertSame(LocalTime::of(9)->toIso8601(), $schedule->opensAt->toIso8601());
        self::assertSame(LocalTime::of(17, 30)->toIso8601(), $schedule->closesAt->toIso8601());
        self::assertSame(LocalDate::of(2026, 11, 8)->toIso8601(), $schedule->published->toIso8601());
        self::assertSame('2026-11-08 10:15', $schedule->createdAt->format('Y-m-d H:i'));

        $data = $serializer->toArray($schedule);

        self::assertSame($schedule->at->toIso8601(), $data['at']);
        self::assertSame('2026-11-08', $data['day']);
        self::assertSame($schedule->opensAt->toIso8601(), $data['opensAt']);
        self::assertSame('17:30', $data['closesAt']);
        self::assertSame('08.11.2026', $data['published']);
        self::assertSame('2026-11-08 10:15', $data['createdAt']);
    }

    public function testInvalidTemporalValuesBecomeViolations(): void
    {
        try {
            $this->serializer()->fromArray(
                [
                    'at' => 5,
                    'day' => 'nope',
                    'opensAt' => '9am',
                    'closesAt' => 'late',
                    'published' => '2026-11-08',
                    'createdAt' => 'garbage',
                ],
                Schedule::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    'at' => 'serializer.invalid-value',
                    'day' => 'serializer.invalid-value',
                    'opensAt' => 'serializer.invalid-value',
                    'closesAt' => 'serializer.invalid-value',
                    'published' => 'serializer.invalid-value',
                    'createdAt' => 'serializer.invalid-value',
                ],
                self::violations($exception),
            );
        }
    }

    public function testToArrayUsesKeysAndSkipsIgnoredAndUninitialized(): void
    {
        $customer = new Customer(
            id: 1,
            firstName: 'Ada',
            nickname: null,
            email: 'ada@example.test',
            status: Status::BLOCKED,
            priority: Priority::HIGH,
            level: Level::EXPERT,
            billingAddress: new Address('Main 1', 'Prague'),
            addresses: [
                new Address('Side 2', 'Brno'),
            ],
            tagIds: [
                3,
            ],
        );

        self::assertSame(
            [
                'id' => 1,
                'first_name' => 'Ada',
                'nickname' => null,
                'e-mail' => 'ada@example.test',
                'active' => true,
                'balance' => 0.0,
                'status' => 'blocked',
                'priority' => 2,
                'level' => 'EXPERT',
                'billing_address' => [
                    'street' => 'Main 1',
                    'city' => 'Prague',
                ],
                'addresses' => [
                    [
                        'street' => 'Side 2',
                        'city' => 'Brno',
                    ],
                ],
                'tag_ids' => [
                    3,
                ],
                'prices' => [],
            ],
            $this->serializer()->toArray($customer),
        );

        $plain = new Plain();

        self::assertArrayNotHasKey('uninitialized', $this->serializer()->toArray($plain));
    }

    public function testToArrayListAndFromArrayList(): void
    {
        $serializer = $this->serializer();
        $addresses = $serializer->fromArrayList(
            [
                [
                    'street' => 'A',
                    'city' => 'B',
                ],
                [
                    'street' => 'C',
                    'city' => 'D',
                ],
            ],
            Address::class,
        );

        self::assertCount(2, $addresses);
        self::assertSame(
            [
                [
                    'street' => 'A',
                    'city' => 'B',
                ],
                [
                    'street' => 'C',
                    'city' => 'D',
                ],
            ],
            $serializer->toArrayList($addresses),
        );
    }

    public function testFromArrayListPrefixesPathsWithTheIndex(): void
    {
        try {
            $this->serializer()->fromArrayList(
                [
                    [
                        'street' => 'A',
                    ],
                    'not an object',
                ],
                Address::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    '0.city' => 'serializer.missing-key',
                    '1' => 'validator.common.wrong-type',
                ],
                self::violations($exception),
            );
        }
    }

    public function testFromArrayListRequiresAList(): void
    {
        try {
            $this->serializer()->fromArrayList(
                [
                    'a' => [],
                ],
                Address::class,
            );

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('root must be a list', $exception->getMessage());
        }
    }

    public function testValidationRunsAfterMapping(): void
    {
        try {
            $this->serializer()->fromArray(
                [
                    'quantity' => '-1',
                ],
                Validated::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertSame(
                [
                    'quantity',
                ],
                \array_keys(self::violations($exception)),
            );
        }

        try {
            $this->serializer()->fromArrayList(
                [
                    [
                        'quantity' => '1',
                    ],
                    [
                        'quantity' => '-2',
                    ],
                ],
                Validated::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertSame(
                [
                    '1.quantity',
                ],
                \array_keys(self::violations($exception)),
            );
        }
    }

    public function testObjectAndLiteralTypes(): void
    {
        $payload = new \stdClass();
        $plain = $this->serializer()->fromArray(
            [
                'payload' => $payload,
                'agreed' => true,
                'uninitialized' => 'set',
            ],
            Plain::class,
        );

        self::assertSame($payload, $plain->payload);
    }

    public function testClassLevelViolationsInListsUseTheIndexAsPath(): void
    {
        try {
            $this->serializer()->fromArrayList(
                [
                    [
                        'from' => 1,
                        'to' => 2,
                    ],
                    [
                        'from' => 5,
                        'to' => 1,
                    ],
                ],
                Range::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertCount(1, $exception->violations);
            self::assertSame('1', $exception->violations[0]->propertyPath);
        }
    }

    public function testSkipValidationNeedsNoValidator(): void
    {
        $serializer = new ArraySerializer(
            skipValidation: true,
        );

        self::assertSame(
            -1,
            $serializer->fromArray(
                [
                    'quantity' => '-1',
                ],
                Validated::class,
            )->quantity,
        );
    }

    public function testValidatorIsRequiredUnlessValidationIsSkipped(): void
    {
        try {
            new ArraySerializer();

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('ArraySerializer', $exception->getMessage());
        }
    }

    public function testCircularReferencesFailToEncode(): void
    {
        $first = new Node();
        $second = new Node();
        $first->next = $second;
        $second->next = $first;

        try {
            $this->serializer()->toArray($first);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('Circular reference', $exception->getMessage());
        }
    }

    public function testUninstantiableClassesFail(): void
    {
        try {
            $this->serializer()->fromArray([], AbstractTarget::class);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('cannot be instantiated', $exception->getMessage());
        }
    }

    public function testConstructorFailuresAreWrapped(): void
    {
        try {
            $this->serializer()->fromArray(
                [
                    'value' => 'x',
                ],
                ThrowingConstructor::class,
            );

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertInstanceOf(\LogicException::class, $exception->getPrevious());
        }
    }

    public function testConvertersMustImplementTheInterface(): void
    {
        try {
            $this->serializer()->fromArray([], BadConverter::class);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('must implement ValueConverterInterface', $exception->getMessage());
        }
    }

    private function serializer(
        bool $skipUnknownKeys = true,
    ): ArraySerializer {
        return new ArraySerializer(
            validator: new Validator(new Container()),
            skipUnknownKeys: $skipUnknownKeys,
        );
    }

    /**
     * @return array<string, string>
     */
    private static function violations(
        ValidationException $exception,
    ): array {
        $codes = [];

        foreach ($exception->violations as $violation) {
            $codes[$violation->propertyPath] = (string) $violation->code->value;
        }

        return $codes;
    }
}
