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
use Fixture\Serializer\ClassicConstructor;
use Fixture\Serializer\Contact;
use Fixture\Serializer\Customer;
use Fixture\Serializer\Status;
use Fixture\Serializer\Validated;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Serializer\CsvSerializer;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\SerializerManager;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\Validator;

class CsvSerializerTest extends TestCase
{
    public function testDecode(): void
    {
        $csv = "\xEF\xBB\xBF" .
            "id;full_name;home_address.street;home_address.city;active;balance;status\r\n" .
            "7;\"Lovelace; Ada\nCountess\";Main 1;Prague;no;1.5;blocked\r\n" .
            "\r\n" .
            "8;Bob;;;;;\r\n";

        $contacts = $this->serializer(separator: ';')->decode($csv, Contact::class);

        self::assertEquals(
            [
                new Contact(
                    id: 7,
                    fullName: "Lovelace; Ada\nCountess",
                    active: false,
                    balance: 1.5,
                    status: Status::BLOCKED,
                    homeAddress: new Address('Main 1', 'Prague'),
                ),
                new Contact(
                    id: 8,
                    fullName: 'Bob',
                ),
            ],
            $contacts,
        );
    }

    public function testDecodeEmptyInput(): void
    {
        self::assertSame([], $this->serializer()->decode('', Contact::class));
        self::assertSame([], $this->serializer()->decode("id,full_name\n", Contact::class));
    }

    public function testEncode(): void
    {
        $csv = $this->serializer()->encode(
            [
                new Contact(
                    id: 1,
                    fullName: 'Ada, L.',
                    active: false,
                    balance: 12.5,
                    status: Status::BLOCKED,
                    homeAddress: new Address('Main 1', 'Prague'),
                ),
                new Contact(
                    id: 2,
                    fullName: 'Bob',
                    phone: '123',
                ),
            ],
        );

        self::assertSame(
            "id,full_name,phone,active,balance,status,home_address.street,home_address.city\n" .
            "1,\"Ada, L.\",,0,12.5,blocked,\"Main 1\",Prague\n" .
            "2,Bob,123,1,0,active,,\n",
            $csv,
        );
    }

    public function testEncodeWithoutNestedValues(): void
    {
        self::assertSame(
            "id,full_name,phone,active,balance,status,home_address\n" .
            "2,Bob,,1,0,active,\n",
            $this->serializer()->encode(
                [
                    new Contact(
                        id: 2,
                        fullName: 'Bob',
                    ),
                ],
            ),
        );
    }

    public function testEncodeSkipsUnreadableProperties(): void
    {
        self::assertSame(
            "name,suffixed\n" .
            "Ada,Ada!\n",
            $this->serializer()->encode(
                [
                    new ClassicConstructor('Ada'),
                ],
            ),
        );
    }

    public function testEncodeEmptyList(): void
    {
        self::assertSame('', $this->serializer()->encode([]));
    }

    public function testRoundTrip(): void
    {
        $contacts = [
            new Contact(
                id: 1,
                fullName: 'Ada "the first" Lovelace',
                phone: '+420 123',
                active: false,
                balance: 0.25,
                status: Status::BLOCKED,
                homeAddress: new Address('Main 1', 'Prague'),
            ),
            new Contact(
                id: 2,
                fullName: 'Bob',
            ),
        ];

        $serializer = $this->serializer(separator: "\t");

        self::assertEquals($contacts, $serializer->decode($serializer->encode($contacts), Contact::class));
    }

    public function testViolationPathsStartWithTheRowIndex(): void
    {
        try {
            $this->serializer()->decode(
                "id,full_name,home_address.city\n" .
                "1,,Prague\n" .
                "x,Bob,\n",
                Contact::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    '0.fullName' => 'serializer.missing-key',
                    '0.homeAddress.street' => 'serializer.missing-key',
                    '1.id' => 'validator.common.wrong-type',
                ],
                self::violations($exception),
            );
        }
    }

    public function testListsCannotBeDecodedFromACell(): void
    {
        try {
            $this->serializer()->decode(
                "id,first_name,e-mail,tag_ids\n" .
                "1,Ada,ada@example.test,\"1,2\"\n",
                Customer::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    '0.tagIds' => 'validator.common.wrong-type',
                ],
                self::violations($exception),
            );
        }
    }

    public function testListsCannotBeEncodedToACell(): void
    {
        try {
            $this->serializer()->encode(
                [
                    new Customer(
                        id: 1,
                        firstName: 'Ada',
                        nickname: null,
                        email: 'ada@example.test',
                    ),
                ],
            );

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('Customer::$addresses" holds a list or map', $exception->getMessage());
        }
    }

    public function testUnknownColumnsAreRejectedWhenNotSkipped(): void
    {
        try {
            $this->serializer(skipUnknownKeys: false)->decode(
                "id,full_name,nickname,home_address.street,home_address.city,home_address.zip\n" .
                "1,Ada,ada,Main 1,Prague,11000\n",
                Contact::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    '0.homeAddress.zip' => 'serializer.unknown-key',
                    '0.nickname' => 'serializer.unknown-key',
                ],
                self::violations($exception),
            );
        }
    }

    public function testRowsMustMatchTheHeader(): void
    {
        try {
            $this->serializer()->decode(
                "id,full_name\n" .
                "1,Ada,extra\n",
                Contact::class,
            );

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('row 0 has 3 fields, the header has 2', $exception->getMessage());
        }
    }

    public function testValidationRunsPerRow(): void
    {
        try {
            $this->serializer()->decode(
                "quantity\n" .
                "1\n" .
                "-2\n",
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

    public function testSeparatorMustBeASingleCharacter(): void
    {
        try {
            new CsvSerializer(
                skipValidation: true,
                separator: ';;',
            );

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('CSV separator must be a single character', $exception->getMessage());
        }
    }

    public function testEnclosureMustBeASingleCharacter(): void
    {
        try {
            new CsvSerializer(
                skipValidation: true,
                enclosure: '',
            );

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('CSV enclosure must be a single character', $exception->getMessage());
        }
    }

    public function testManagerPassesOptionsThrough(): void
    {
        $csv = new SerializerManager(new Validator(new Container()))->csv(
            separator: ';',
            enclosure: "'",
        );

        self::assertInstanceOf(CsvSerializer::class, $csv);
        self::assertSame(';', $csv->separator);
        self::assertSame("'", $csv->enclosure);
    }

    private function serializer(
        bool $skipUnknownKeys = true,
        string $separator = ',',
    ): CsvSerializer {
        return new CsvSerializer(
            validator: new Validator(new Container()),
            skipUnknownKeys: $skipUnknownKeys,
            separator: $separator,
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
