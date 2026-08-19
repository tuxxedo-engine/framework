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

namespace Tuxxedo\Mail\Signer\Dkim;

use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Middleware\MailWireMiddlewareInterface;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Security\Crypto\CryptoException;
use Tuxxedo\Security\Crypto\Signature\OpensslSignature;

class DkimSigner implements MailWireMiddlewareInterface
{
    /**
     * @param list<string> $signedHeaders
     */
    public function __construct(
        public readonly string $selector,
        public readonly string $domain,
        #[\SensitiveParameter]
        public readonly string $privateKey,
        public readonly DkimCanonicalization $headerCanonicalization = DkimCanonicalization::RELAXED,
        public readonly DkimCanonicalization $bodyCanonicalization = DkimCanonicalization::RELAXED,
        public readonly DkimAlgorithm $algorithm = DkimAlgorithm::RSA_SHA256,
        public readonly array $signedHeaders = [
            'From',
            'To',
            'Subject',
            'Date',
            'Message-ID',
        ],
    ) {
    }

    public function process(
        SerializedMessageInterface $serialized,
    ): SerializedMessageInterface {
        $canonicalBody = BodyCanonicalizer::canonicalize(
            body: $serialized->body,
            mode: $this->bodyCanonicalization,
        );

        $bodyHash = \base64_encode(
            \hash('sha256', $canonicalBody, true),
        );

        $tag = new DkimSignatureTag(
            algorithm: $this->algorithm,
            headerCanonicalization: $this->headerCanonicalization,
            bodyCanonicalization: $this->bodyCanonicalization,
            domain: $this->domain,
            selector: $this->selector,
            signedHeaders: $this->signedHeaders,
            bh: $bodyHash,
            b: '',
            timestamp: \time(),
        );

        $signingInput = DkimSigningInput::build(
            serialized: $serialized,
            tag: $tag,
        );

        $signature = match ($this->algorithm) {
            DkimAlgorithm::RSA_SHA256 => $this->signRsa($signingInput),
            DkimAlgorithm::ED25519_SHA256 => $this->signEd25519($signingInput),
        };

        $completed = $tag->withB(
            b: \base64_encode($signature),
        );

        $dkimHeaderLine = DkimHeaderFolder::fold(
            headerLine: 'DKIM-Signature: ' . $completed->toHeaderValue(),
        );

        return new SerializedMessage(
            source: $serialized->source,
            headers: $dkimHeaderLine . "\r\n" . $serialized->headers,
            body: $serialized->body,
        );
    }

    /**
     * @throws MailException
     */
    private function signRsa(
        string $input,
    ): string {
        $key = \openssl_pkey_get_private($this->privateKey);

        if ($key === false) {
            throw MailException::fromDkimInvalidPrivateKey();
        }

        try {
            return OpensslSignature::sign(
                privateKey: $key,
                opensslAlgorithm: \OPENSSL_ALGO_SHA256,
                payload: $input,
                algorithmIdentifier: DkimAlgorithm::RSA_SHA256->value,
            );
        } catch (CryptoException $e) { // @codeCoverageIgnoreStart
            throw MailException::fromDkimSigningFailed(
                previous: $e,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @throws MailException
     */
    private function signEd25519(
        string $input,
    ): string {
        if (!\function_exists('sodium_crypto_sign_seed_keypair')) {
            throw MailException::fromDkimMissingSodiumExtension(); // @codeCoverageIgnore
        }

        $seed = \base64_decode($this->privateKey, true);

        if ($seed === false || \strlen($seed) !== \SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            throw MailException::fromDkimEd25519InvalidKey();
        }

        $hash = \hash('sha256', $input, true);
        $keypair = \sodium_crypto_sign_seed_keypair($seed);
        $secretKey = \sodium_crypto_sign_secretkey($keypair);

        return \sodium_crypto_sign_detached($hash, $secretKey);
    }
}
