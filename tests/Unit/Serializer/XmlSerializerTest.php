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

use Fixture\Http\Request\Context\BodyContextFixture;
use Fixture\Serializer\Contact;
use Fixture\Serializer\Plain;
use Fixture\Serializer\Status;
use Fixture\Serializer\Validated;
use Fixture\Serializer\XmlLine;
use Fixture\Serializer\XmlMoney;
use Fixture\Serializer\XmlOrder;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Serializer\SerializerManager;
use Tuxxedo\Serializer\XmlSerializer;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\Validator;

class XmlSerializerTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $order = new XmlOrder(
            id: 42,
            customer: 'Ada & Co',
            total: new XmlMoney('12.50', 'EUR'),
            lines: [
                new XmlLine('A-1', 2),
            ],
            tags: [
                'x',
                'y',
            ],
            stock: [
                'A-1' => 3,
            ],
            paid: true,
            status: Status::BLOCKED,
        );

        $serializer = $this->serializer();
        $xml = $serializer->encode($order);

        self::assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<order id="42"><customer>Ada &amp; Co</customer><total currency="EUR">12.50</total>' .
            '<lines><line><sku>A-1</sku><qty>2</qty></line></lines><tag>x</tag><tag>y</tag>' .
            '<stock><A-1>3</A-1></stock><paid>true</paid><status>blocked</status></order>',
            $xml,
        );

        self::assertEquals($order, $serializer->decode($xml, XmlOrder::class));
    }

    public function testDefaultRootFollowsTheCaseStyle(): void
    {
        $contact = new Contact(
            id: 1,
            fullName: 'Ada',
        );

        $serializer = $this->serializer();
        $xml = $serializer->encode($contact);

        self::assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<contact><id>1</id><full_name>Ada</full_name><active>true</active><balance>0</balance><status>active</status></contact>',
            $xml,
        );

        self::assertEquals($contact, $serializer->decode($xml, Contact::class));
    }

    public function testIndent(): void
    {
        self::assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
            "<XmlLine>\n" .
            "  <sku>A-1</sku>\n" .
            "  <qty>2</qty>\n" .
            '</XmlLine>',
            $this->serializer(indent: '  ')->encode(new XmlLine('A-1', 2)),
        );
    }

    public function testDecodeIgnoresRootNameNamespacesCommentsAndInstructions(): void
    {
        $line = $this->serializer()->decode(
            '<?xml version="1.0"?><!-- comment --><x:anything xmlns:x="urn:x">' .
            '<?pi data?><x:sku><![CDATA[A&1]]></x:sku><qty> 2 </qty></x:anything>',
            XmlLine::class,
        );

        self::assertSame('A&1', $line->sku);
        self::assertSame(2, $line->qty);
    }

    public function testEmptyElementsAreMissingKeys(): void
    {
        $order = $this->serializer()->decode(
            '<order id="1"><customer>Ada</customer><total currency="EUR">1</total><note/><paid></paid><lines/></order>',
            XmlOrder::class,
        );

        self::assertNull($order->note);
        self::assertFalse($order->paid);
        self::assertSame([], $order->lines);

        self::assertSame(
            [],
            $this->serializer()->decode(
                '<order id="1"><customer>Ada</customer><total currency="EUR">1</total></order>',
                XmlOrder::class,
            )->lines,
        );

        try {
            $this->serializer()->decode('<XmlLine><sku/></XmlLine>', XmlLine::class);

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    'sku' => 'serializer.missing-key',
                    'qty' => 'serializer.missing-key',
                ],
                self::violations($exception),
            );
        }
    }

    public function testUnknownElementsAndAttributesWhenNotSkipped(): void
    {
        try {
            $this->serializer(skipUnknownKeys: false)->decode(
                '<XmlLine extra="1"><sku>A</sku><qty>1</qty><color>red</color></XmlLine>',
                XmlLine::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    'extra' => 'serializer.unknown-key',
                    'color' => 'serializer.unknown-key',
                ],
                self::violations($exception),
            );
        }
    }

    public function testKeyAndOnly(): void
    {
        $serializer = $this->serializer();

        $person = $serializer->decode(
            xml: '<root><person><name>Ada</name><age>36</age></person></root>',
            className: BodyContextFixture::class,
            key: 'person',
            only: [
                'name',
            ],
        );

        self::assertSame('Ada', $person->name);
        self::assertSame(0, $person->age);

        $lines = $serializer->decodeList(
            xml: '<root><lines><line><sku>A</sku><qty>1</qty></line><line><sku>B</sku><qty>2</qty></line></lines></root>',
            className: XmlLine::class,
            key: 'lines',
        );

        self::assertCount(2, $lines);
        self::assertSame('B', $lines[1]->sku);

        self::assertSame(
            [],
            $serializer->decodeList(
                xml: '<root/>',
                className: XmlLine::class,
                key: 'lines',
            ),
        );
    }

    public function testDecodeListPathsStartWithTheIndex(): void
    {
        try {
            $this->serializer()->decodeList(
                '<list><XmlLine><sku>A</sku><qty>1</qty></XmlLine><XmlLine><sku>B</sku></XmlLine></list>',
                XmlLine::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertEquals(
                [
                    '1.qty' => 'serializer.missing-key',
                ],
                self::violations($exception),
            );
        }
    }

    public function testEncodeList(): void
    {
        $serializer = $this->serializer();
        $xml = $serializer->encodeList(
            [
                new XmlLine('A', 1),
                new XmlLine('B', 2),
            ],
        );

        self::assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<list><XmlLine><sku>A</sku><qty>1</qty></XmlLine><XmlLine><sku>B</sku><qty>2</qty></XmlLine></list>',
            $xml,
        );

        self::assertCount(2, $serializer->decodeList($xml, XmlLine::class));
        self::assertSame('<?xml version="1.0" encoding="UTF-8"?><list/>', $serializer->encodeList([]));
    }

    public function testValidationRunsAfterMapping(): void
    {
        $this->expectException(ValidationException::class);

        $this->serializer()->decode('<v><quantity>-1</quantity></v>', Validated::class);
    }

    public function testDoctypeIsRejected(): void
    {
        try {
            $this->serializer()->decode('<!DOCTYPE x [<!ENTITY a "b">]><x><sku>&a;</sku></x>', XmlLine::class);

            self::fail('Expected SerializerInputException was not thrown');
        } catch (SerializerInputException $exception) {
            self::assertStringContainsString('DOCTYPE', $exception->getMessage());
        }
    }

    public function testMalformedInput(): void
    {
        foreach (['<x>', 'not xml', '<a></b>'] as $xml) {
            try {
                $this->serializer()->decode($xml, XmlLine::class);

                self::fail('Expected SerializerInputException was not thrown');
            } catch (SerializerInputException $exception) {
                self::assertStringContainsString('Malformed XML input', $exception->getMessage());
            }
        }

        try {
            $this->serializer()->decode(' ', XmlLine::class);

            self::fail('Expected SerializerInputException was not thrown');
        } catch (SerializerInputException $exception) {
            self::assertStringContainsString('XML input is empty', $exception->getMessage());
        }
    }

    public function testIllegalCharactersFailToEncode(): void
    {
        try {
            $this->serializer()->encode(new XmlLine("A\x01", 1));

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('not allowed in XML', $exception->getMessage());
        }
    }

    public function testInvalidNamesFailToEncode(): void
    {
        try {
            $this->serializer()->encode(
                new XmlOrder(
                    id: 1,
                    customer: 'Ada',
                    total: new XmlMoney('1', 'EUR'),
                    stock: [
                        '1st' => 1,
                    ],
                ),
            );

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('"1st" is not a valid XML', $exception->getMessage());
        }
    }

    public function testUntypedArraysFailToEncode(): void
    {
        $plain = new Plain();
        $plain->anything = [
            'a' => 1,
        ];

        try {
            $this->serializer()->encode($plain);

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('Plain::$anything" holds a value that cannot be written as XML', $exception->getMessage());
        }
    }

    public function testManagerPassesOptionsThrough(): void
    {
        $xml = new SerializerManager(new Validator(new Container()))->xml(
            skipValidation: true,
            indent: "\t",
        );

        self::assertInstanceOf(XmlSerializer::class, $xml);
        self::assertSame("\t", $xml->indent);
    }

    private function serializer(
        bool $skipUnknownKeys = true,
        ?string $indent = null,
    ): XmlSerializer {
        return new XmlSerializer(
            validator: new Validator(new Container()),
            skipUnknownKeys: $skipUnknownKeys,
            indent: $indent,
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
