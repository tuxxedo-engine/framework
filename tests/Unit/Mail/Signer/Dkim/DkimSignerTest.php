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

namespace Unit\Mail\Signer\Dkim;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Signer\Dkim\DkimAlgorithm;
use Tuxxedo\Mail\Signer\Dkim\DkimCanonicalization;
use Tuxxedo\Mail\Signer\Dkim\DkimSignatureTag;
use Tuxxedo\Mail\Signer\Dkim\DkimSigner;
use Tuxxedo\Mail\Signer\Dkim\DkimSigningInput;

#[RequiresPhpExtension('openssl')]
#[RequiresPhpExtension('sodium')]
class DkimSignerTest extends TestCase
{
    private static string $rsaPrivateKey;
    private static string $rsaPublicKey;
    private static string $ed25519SeedBase64;

    /**
     * @var non-empty-string
     */
    private static string $ed25519PublicKey;

    public static function setUpBeforeClass(): void
    {
        $rsa = \openssl_pkey_new(
            [
                'private_key_type' => \OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ],
        );

        if ($rsa === false) {
            self::fail('Failed to generate test RSA keypair');
        }

        $privatePem = '';

        if (\openssl_pkey_export($rsa, $privatePem) === false) {
            self::fail('Failed to export RSA private key');
        }

        if (!\is_string($privatePem)) {
            self::fail('Exported RSA private key is not a string');
        }

        self::$rsaPrivateKey = $privatePem;

        $details = \openssl_pkey_get_details($rsa);

        if ($details === false || !isset($details['key']) || !\is_string($details['key'])) {
            self::fail('Failed to export RSA public key');
        }

        self::$rsaPublicKey = $details['key'];

        $seed = \random_bytes(\SODIUM_CRYPTO_SIGN_SEEDBYTES);
        $keypair = \sodium_crypto_sign_seed_keypair($seed);

        self::$ed25519SeedBase64 = \base64_encode($seed);
        self::$ed25519PublicKey = \sodium_crypto_sign_publickey($keypair);
    }

    private static function newSerialized(
        string $headers = "From: alice@example.com\r\nTo: bob@example.com\r\nSubject: Hi\r\nDate: Wed, 01 Jan 2025 00:00:00 +0000\r\nMessage-ID: <abc@example.com>",
        string $body = "hello world\r\n",
    ): SerializedMessage {
        $source = new Message(
            from: new Address(
                email: 'alice@example.com',
            ),
            to: [
                new Address(
                    email: 'bob@example.com',
                ),
            ],
            subject: 'Hi',
        );

        return new SerializedMessage(
            source: $source,
            headers: $headers,
            body: $body,
        );
    }

    private static function extractDkimHeader(
        string $headers,
    ): string {
        $unfolded = \preg_replace('/\r\n[ \t]+/', ' ', $headers) ?? $headers;

        foreach (\explode("\r\n", $unfolded) as $line) {
            if (\str_starts_with($line, 'DKIM-Signature:')) {
                return \substr($line, \strlen('DKIM-Signature:'));
            }
        }

        self::fail('DKIM-Signature header not present in output');
    }

    /**
     * @return array<string, string>
     */
    private static function parseTags(
        string $headerValue,
    ): array {
        $tags = [];

        foreach (\explode(';', $headerValue) as $part) {
            $trimmed = \trim($part);

            if ($trimmed === '') {
                continue;
            }

            [$name, $value] = \explode('=', $trimmed, 2);
            $tags[\trim($name)] = \trim($value);
        }

        return $tags;
    }

    private static function decodeBase64(
        string $value,
    ): string {
        $decoded = \base64_decode($value, true);

        if ($decoded === false) {
            self::fail('Emitted b= value was not valid base64');
        }

        return $decoded;
    }

    private static function rebuildSigningInput(
        MessageInterface $source,
        string $originalHeaders,
        string $body,
        DkimSignatureTag $signingTag,
    ): string {
        return DkimSigningInput::build(
            serialized: new SerializedMessage(
                source: $source,
                headers: $originalHeaders,
                body: $body,
            ),
            tag: $signingTag,
        );
    }

    /**
     * @param array<string, string> $tags
     */
    private static function tagFromEmitted(
        array $tags,
        DkimAlgorithm $algorithm,
    ): DkimSignatureTag {
        [$hc, $bc] = \explode('/', $tags['c']);

        return new DkimSignatureTag(
            algorithm: $algorithm,
            headerCanonicalization: DkimCanonicalization::from($hc),
            bodyCanonicalization: DkimCanonicalization::from($bc),
            domain: $tags['d'],
            selector: $tags['s'],
            signedHeaders: \explode(':', $tags['h']),
            bh: $tags['bh'],
            b: '',
            timestamp: isset($tags['t'])
                ? (int) $tags['t']
                : null,
        );
    }

    public function testProcessPrependsDkimSignatureHeaderAndKeepsBodyAndSourceUnchanged(): void
    {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: self::$rsaPrivateKey,
        );

        $serialized = self::newSerialized();

        $result = $signer->process(
            serialized: $serialized,
        );

        self::assertStringStartsWith('DKIM-Signature:', $result->headers);
        self::assertStringContainsString("\r\nFrom: alice@example.com", $result->headers);
        self::assertSame($serialized->body, $result->body);
        self::assertSame($serialized->source, $result->source);
    }

    public function testEmittedRsaSignatureVerifiesAgainstPublicKey(): void
    {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: self::$rsaPrivateKey,
        );

        $serialized = self::newSerialized();
        $result = $signer->process(
            serialized: $serialized,
        );

        $tags = self::parseTags(
            headerValue: self::extractDkimHeader(
                headers: $result->headers,
            ),
        );

        $signingTag = self::tagFromEmitted(
            tags: $tags,
            algorithm: DkimAlgorithm::RSA_SHA256,
        );

        $signingInput = self::rebuildSigningInput(
            source: $serialized->source,
            originalHeaders: $serialized->headers,
            body: $serialized->body,
            signingTag: $signingTag,
        );

        $publicKey = \openssl_pkey_get_public(self::$rsaPublicKey);

        if ($publicKey === false) {
            self::fail('Failed to import RSA public key');
        }

        $verified = \openssl_verify(
            $signingInput,
            self::decodeBase64($tags['b']),
            $publicKey,
            \OPENSSL_ALGO_SHA256,
        );

        self::assertSame(1, $verified);
    }

    public function testEmittedEd25519SignatureVerifiesAgainstPublicKey(): void
    {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: self::$ed25519SeedBase64,
            algorithm: DkimAlgorithm::ED25519_SHA256,
        );

        $serialized = self::newSerialized();
        $result = $signer->process(
            serialized: $serialized,
        );

        $tags = self::parseTags(
            headerValue: self::extractDkimHeader(
                headers: $result->headers,
            ),
        );

        $signingTag = self::tagFromEmitted(
            tags: $tags,
            algorithm: DkimAlgorithm::ED25519_SHA256,
        );

        $signingInput = self::rebuildSigningInput(
            source: $serialized->source,
            originalHeaders: $serialized->headers,
            body: $serialized->body,
            signingTag: $signingTag,
        );

        $signature = self::decodeBase64($tags['b']);

        if ($signature === '') {
            self::fail('Emitted b= decoded to an empty signature');
        }

        $verified = \sodium_crypto_sign_verify_detached(
            $signature,
            \hash('sha256', $signingInput, true),
            self::$ed25519PublicKey,
        );

        self::assertTrue($verified);
    }

    /**
     * @return \Generator<string, array{0: DkimCanonicalization, 1: DkimCanonicalization, 2: string}>
     */
    public static function providesCanonicalizationModes(): \Generator
    {
        yield 'simple/simple' => [
            DkimCanonicalization::SIMPLE,
            DkimCanonicalization::SIMPLE,
            'simple/simple',
        ];

        yield 'simple/relaxed' => [
            DkimCanonicalization::SIMPLE,
            DkimCanonicalization::RELAXED,
            'simple/relaxed',
        ];

        yield 'relaxed/simple' => [
            DkimCanonicalization::RELAXED,
            DkimCanonicalization::SIMPLE,
            'relaxed/simple',
        ];

        yield 'relaxed/relaxed' => [
            DkimCanonicalization::RELAXED,
            DkimCanonicalization::RELAXED,
            'relaxed/relaxed',
        ];
    }

    #[DataProvider('providesCanonicalizationModes')]
    public function testCanonicalizationTagReflectsConfiguredModes(
        DkimCanonicalization $header,
        DkimCanonicalization $body,
        string $expectedC,
    ): void {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: self::$rsaPrivateKey,
            headerCanonicalization: $header,
            bodyCanonicalization: $body,
        );

        $result = $signer->process(
            serialized: self::newSerialized(),
        );

        $tags = self::parseTags(
            headerValue: self::extractDkimHeader(
                headers: $result->headers,
            ),
        );

        self::assertSame($expectedC, $tags['c']);
    }

    public function testAllExpectedTagFragmentsArePresent(): void
    {
        $signer = new DkimSigner(
            selector: 'my-selector',
            domain: 'sender.example',
            privateKey: self::$rsaPrivateKey,
        );

        $result = $signer->process(
            serialized: self::newSerialized(),
        );

        $tags = self::parseTags(
            headerValue: self::extractDkimHeader(
                headers: $result->headers,
            ),
        );

        self::assertSame('1', $tags['v']);
        self::assertSame(DkimAlgorithm::RSA_SHA256->value, $tags['a']);
        self::assertSame('sender.example', $tags['d']);
        self::assertSame('my-selector', $tags['s']);
        self::assertArrayHasKey('h', $tags);
        self::assertArrayHasKey('bh', $tags);
        self::assertArrayHasKey('t', $tags);
        self::assertMatchesRegularExpression('/^\d+$/', $tags['t']);
        self::assertArrayHasKey('b', $tags);
        self::assertNotSame('', $tags['b']);
    }

    public function testCustomSignedHeadersAreReflectedInHeaderTag(): void
    {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: self::$rsaPrivateKey,
            signedHeaders: [
                'From',
                'Subject',
                'List-Unsubscribe',
            ],
        );

        $result = $signer->process(
            serialized: self::newSerialized(),
        );

        $tags = self::parseTags(
            headerValue: self::extractDkimHeader(
                headers: $result->headers,
            ),
        );

        self::assertSame('From:Subject:List-Unsubscribe', $tags['h']);
    }

    public function testInvalidRsaPrivateKeyThrows(): void
    {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: 'not-a-pem-key',
        );

        try {
            $signer->process(
                serialized: self::newSerialized(),
            );
            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('DKIM', $exception->getMessage());
        }
    }

    public function testEd25519WithMalformedBase64Throws(): void
    {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: '!!! not base64 !!!',
            algorithm: DkimAlgorithm::ED25519_SHA256,
        );

        try {
            $signer->process(
                serialized: self::newSerialized(),
            );
            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('Ed25519', $exception->getMessage());
        }
    }

    public function testEd25519WithWrongLengthSeedThrows(): void
    {
        $signer = new DkimSigner(
            selector: 'default',
            domain: 'example.com',
            privateKey: \base64_encode('too-short'),
            algorithm: DkimAlgorithm::ED25519_SHA256,
        );

        try {
            $signer->process(
                serialized: self::newSerialized(),
            );
            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('Ed25519', $exception->getMessage());
        }
    }
}
