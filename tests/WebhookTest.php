<?php

declare(strict_types=1);

namespace Glytos\Tests;

use Glytos\Webhook;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    // A fixed vector produced by the server signer (HMAC-SHA256 over "{ts}.{body}"),
    // so this SDK is proven byte-for-byte compatible with how Glytos signs deliveries.
    private const SECRET = 'whsec_test_secret';
    private const TS = '1710000000';
    private const BODY = '{"event":"call.completed","id":"evt_123"}';
    private const SIG = '356d865446082d49c6bfa57299c45900ab0d6681363426341acbe7c8f07ba025';

    private function header(string $ts, string $sig): string
    {
        return 't=' . $ts . ',v1=' . $sig;
    }

    public function testAcceptsAKnownSignature(): void
    {
        // Tolerance 0 so the fixed historical timestamp is not rejected as too old.
        self::assertTrue(Webhook::verify(self::BODY, $this->header(self::TS, self::SIG), self::SECRET, 0));
    }

    public function testRejectsATamperedBody(): void
    {
        self::assertFalse(Webhook::verify(self::BODY . 'x', $this->header(self::TS, self::SIG), self::SECRET, 0));
    }

    public function testRejectsAWrongSecret(): void
    {
        self::assertFalse(Webhook::verify(self::BODY, $this->header(self::TS, self::SIG), 'wrong-secret', 0));
    }

    public function testRejectsAMalformedHeader(): void
    {
        self::assertFalse(Webhook::verify(self::BODY, 'garbage', self::SECRET, 0));
        self::assertFalse(Webhook::verify(self::BODY, 't=' . self::TS, self::SECRET, 0));
    }

    public function testRejectsAnExpiredDelivery(): void
    {
        // With the default tolerance the 2024 timestamp is far outside the window.
        self::assertFalse(Webhook::verify(self::BODY, $this->header(self::TS, self::SIG), self::SECRET));
    }

    public function testAcceptsAFreshDelivery(): void
    {
        $ts = (string) time();
        $body = '{"hello":"world"}';
        $secret = 'whsec_live';
        $sig = hash_hmac('sha256', $ts . '.' . $body, $secret);
        self::assertTrue(Webhook::verify($body, $this->header($ts, $sig), $secret));
    }
}
