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

use Fixture\Serializer\Address;
use Fixture\Serializer\Customer;
use Fixture\Serializer\EmptyObject;
use Fixture\Serializer\Plain;
use Fixture\Serializer\Priority;
use Fixture\Serializer\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Serializer\ArraySerializer;
use Tuxxedo\Serializer\JsonSerializer;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\Validator;

class JsonSerializerTest extends TestCase
{
    public function testDecodeAndEncodeRoundTrip(): void
    {
        $json = '{"id":7,"first_name":"Ada","nickname":null,"e-mail":"ada@example.test","active":false,' .
            '"balance":12.5,"status":"blocked","priority":2,"level":"EXPERT",' .
            '"billing_address":{"street":"Main 1","city":"Prague"},' .
            '"addresses":[{"street":"Side 2","city":"Brno"}],"tag_ids":[1,2],"prices":{"base":9.5}}';

        $serializer = $this->serializer();
        $customer = $serializer->decode($json, Customer::class);

        self::assertSame(7, $customer->id);
        self::assertSame(Status::BLOCKED, $customer->status);
        self::assertSame(Priority::HIGH, $customer->priority);
        self::assertEquals(new Address('Main 1', 'Prague'), $customer->billingAddress);
        self::assertSame($json, $serializer->encode($customer));
    }

    public function testDecodeIsStrict(): void
    {
        try {
            $this->serializer()->decode(
                '{"id":"7","first_name":1,"e-mail":"a@b.test","active":"true","balance":"1.5","priority":"2"}',
                Customer::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            $codes = [];

            foreach ($exception->violations as $violation) {
                $codes[$violation->propertyPath] = $violation->code->value;
            }

            self::assertEquals(
                [
                    'id' => 'validator.common.wrong-type',
                    'firstName' => 'validator.common.wrong-type',
                    'active' => 'validator.common.wrong-type',
                    'balance' => 'validator.common.wrong-type',
                    'priority' => 'serializer.invalid-value',
                ],
                $codes,
            );
        }
    }

    public function testDecodeAcceptsIntegersForFloats(): void
    {
        $plain = $this->serializer()->decode(
            '{"count":1,"text":"x","uninitialized":"y"}',
            Plain::class,
        );

        self::assertSame(1, $plain->count);

        $customer = $this->serializer()->decode(
            '{"id":1,"first_name":"Ada","nickname":null,"e-mail":"a@b.test","balance":3}',
            Customer::class,
        );

        self::assertSame(3.0, $customer->balance);
    }

    public function testDecodeAcceptsAnEmptyObject(): void
    {
        self::assertInstanceOf(
            EmptyObject::class,
            $this->serializer()->decode('{}', EmptyObject::class),
        );
    }

    #[DataProvider('nonObjectRootProvider')]
    public function testDecodeRequiresAnObjectRoot(
        string $json,
    ): void {
        try {
            $this->serializer()->decode($json, Address::class);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('JSON root must be an object', $exception->getMessage());
        }
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function nonObjectRootProvider(): \Generator
    {
        yield 'list' => [
            '[1]',
        ];

        yield 'string' => [
            '"text"',
        ];

        yield 'null' => [
            'null',
        ];
    }

    public function testDecodeWithKeyAndOnly(): void
    {
        $serializer = $this->serializer();

        $address = $serializer->decode(
            json: '{"data":{"street":"A","city":"B","zip":"1"}}',
            className: Address::class,
            key: 'data',
            only: [
                'street',
                'city',
            ],
        );

        self::assertEquals(new Address('A', 'B'), $address);

        $addresses = $serializer->decodeList(
            json: '{"data":[{"street":"A","city":"B"}]}',
            className: Address::class,
            key: 'data',
        );

        self::assertCount(1, $addresses);
    }

    public function testDecodeWithKeyRequiresAnObjectThere(): void
    {
        try {
            $this->serializer()->decode(
                json: '{"data":[{"street":"A"}]}',
                className: Address::class,
                key: 'data',
            );

            self::fail('Expected SerializerInputException was not thrown');
        } catch (SerializerInputException $exception) {
            self::assertStringContainsString('JSON root must be an object', $exception->getMessage());
        }
    }

    public function testDecodeListRequiresAnArrayRoot(): void
    {
        $this->expectException(SerializerInputException::class);

        $this->serializer()->decodeList('"text"', Address::class);
    }

    public function testDecodeListAndEncodeList(): void
    {
        $serializer = $this->serializer();
        $json = '[{"street":"A","city":"B"},{"street":"C","city":"D"}]';
        $addresses = $serializer->decodeList($json, Address::class);

        self::assertCount(2, $addresses);
        self::assertSame('D', $addresses[1]->city);
        self::assertSame($json, $serializer->encodeList($addresses));
        self::assertSame('[]', $serializer->encodeList([]));
    }

    public function testDecodeListRequiresAListRoot(): void
    {
        try {
            $this->serializer()->decodeList('{"street":"A"}', Address::class);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('JSON root must be a list', $exception->getMessage());
        }
    }

    public function testMalformedInputIsWrapped(): void
    {
        try {
            $this->serializer()->decode('{"street":', Address::class);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
            self::assertStringContainsString('Malformed JSON input', $exception->getMessage());
        }
    }

    public function testDepthIsApplied(): void
    {
        $serializer = new JsonSerializer(
            skipValidation: true,
            depth: 1,
        );

        try {
            $serializer->decode('{"street":"A","city":"B"}', Address::class);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
        }
    }

    public function testFlagsAreApplied(): void
    {
        $serializer = new JsonSerializer(
            skipValidation: true,
            flags: \JSON_PRETTY_PRINT,
        );

        self::assertSame(
            "{\n    \"street\": \"A\",\n    \"city\": \"B\"\n}",
            $serializer->encode(new Address('A', 'B')),
        );
    }

    public function testEncodeEmptyObject(): void
    {
        self::assertSame('{}', $this->serializer()->encode(new EmptyObject()));
    }

    public function testUnencodableValuesAreWrapped(): void
    {
        $plain = new Plain();
        $plain->anything = \NAN;

        try {
            $this->serializer()->encode($plain);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
            self::assertStringContainsString('cannot be encoded as JSON', $exception->getMessage());
        }
    }

    public function testFormatsShareTheSameObjectModel(): void
    {
        $validator = new Validator(new Container());
        $json = new JsonSerializer(
            validator: $validator,
        );

        $array = new ArraySerializer(
            validator: $validator,
        );

        $fromForm = $array->fromArray(
            [
                'id' => '7',
                'first_name' => 'Ada',
                'nickname' => '',
                'e-mail' => 'ada@example.test',
                'active' => 'on',
                'priority' => '2',
            ],
            Customer::class,
        );

        $fromJson = $json->decode($json->encode($fromForm), Customer::class);

        self::assertEquals($fromForm, $fromJson);
        self::assertSame($array->toArray($fromForm), $array->toArray($fromJson));
    }

    private function serializer(): JsonSerializer
    {
        return new JsonSerializer(
            validator: new Validator(new Container()),
        );
    }
}
