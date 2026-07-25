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

namespace Tuxxedo\Security\Crypto\Signature;

use Tuxxedo\Security\Crypto\CryptoException;

class EcdsaSignatureCodec
{
    public function __construct(
        private readonly string $algorithmIdentifier,
        private readonly int $componentLength,
    ) {
    }

    /**
     * @throws CryptoException
     */
    public function derToJose(
        string $der,
    ): string {
        $position = 0;

        if (($der[$position] ?? '') !== "\x30") {
            throw CryptoException::fromMalformedEcdsaSignature(
                algorithmIdentifier: $this->algorithmIdentifier,
            );
        }

        $position++;

        $position = $this->skipDerLength(
            der: $der,
            position: $position,
        );

        if (($der[$position] ?? '') !== "\x02") {
            throw CryptoException::fromMalformedEcdsaSignature(
                algorithmIdentifier: $this->algorithmIdentifier,
            );
        }

        $position++;

        [$firstIntegerLength, $position] = $this->readDerLength(
            der: $der,
            position: $position,
        );

        $firstIntegerBytes = \substr($der, $position, $firstIntegerLength);
        $position += $firstIntegerLength;

        if (($der[$position] ?? '') !== "\x02") {
            throw CryptoException::fromMalformedEcdsaSignature(
                algorithmIdentifier: $this->algorithmIdentifier,
            );
        }

        $position++;

        [$secondIntegerLength, $position] = $this->readDerLength(
            der: $der,
            position: $position,
        );

        $secondIntegerBytes = \substr($der, $position, $secondIntegerLength);
        $firstIntegerBytes = \ltrim($firstIntegerBytes, "\x00");
        $secondIntegerBytes = \ltrim($secondIntegerBytes, "\x00");

        return \str_pad($firstIntegerBytes, $this->componentLength, "\x00", \STR_PAD_LEFT) .
            \str_pad($secondIntegerBytes, $this->componentLength, "\x00", \STR_PAD_LEFT);
    }

    /**
     * @throws CryptoException
     */
    public function joseToDer(
        string $jose,
    ): string {
        $firstIntegerBytes = \substr($jose, 0, $this->componentLength);
        $secondIntegerBytes = \substr($jose, $this->componentLength);
        $firstIntegerBytes = \ltrim($firstIntegerBytes, "\x00");
        $secondIntegerBytes = \ltrim($secondIntegerBytes, "\x00");

        if ($firstIntegerBytes === '') {
            $firstIntegerBytes = "\x00";
        }

        if ($secondIntegerBytes === '') {
            $secondIntegerBytes = "\x00";
        }

        if (\ord($firstIntegerBytes[0]) >= 0x80) {
            $firstIntegerBytes = "\x00" . $firstIntegerBytes;
        }

        if (\ord($secondIntegerBytes[0]) >= 0x80) {
            $secondIntegerBytes = "\x00" . $secondIntegerBytes;
        }

        $encodedFirstInteger = "\x02" . $this->encodeDerLength(\strlen($firstIntegerBytes)) . $firstIntegerBytes;
        $encodedSecondInteger = "\x02" . $this->encodeDerLength(\strlen($secondIntegerBytes)) . $secondIntegerBytes;
        $sequenceContent = $encodedFirstInteger . $encodedSecondInteger;

        return "\x30" . $this->encodeDerLength(\strlen($sequenceContent)) . $sequenceContent;
    }

    /**
     * @return array{int, int}
     *
     * @throws CryptoException
     */
    private function readDerLength(
        string $der,
        int $position,
    ): array {
        $firstByte = \ord($der[$position] ?? "\x00");
        $position++;

        if ($firstByte < 0x80) {
            return [
                $firstByte,
                $position,
            ];
        }

        $lengthByteCount = $firstByte & 0x7f;
        $length = 0;

        for ($i = 0; $i < $lengthByteCount; $i++) {
            $length = ($length << 8) | \ord($der[$position] ?? "\x00");

            $position++;
        }

        return [
            $length,
            $position,
        ];
    }

    /**
     * @throws CryptoException
     */
    private function skipDerLength(
        string $der,
        int $position,
    ): int {
        [, $newPosition] = $this->readDerLength(
            der: $der,
            position: $position,
        );

        return $newPosition;
    }

    /**
     * @param int<0, max> $length
     *
     * @throws CryptoException
     */
    private function encodeDerLength(
        int $length,
    ): string {
        if ($length < 0x80) {
            return \chr($length);
        }

        if ($length < 0x100) {
            return "\x81" . \chr($length);
        }

        if ($length < 0x10000) {
            return "\x82" . \chr($length >> 8) . \chr($length & 0xff);
        }

        throw CryptoException::fromMalformedEcdsaSignature(
            algorithmIdentifier: $this->algorithmIdentifier,
        );
    }
}
