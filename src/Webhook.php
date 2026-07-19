<?php

declare(strict_types=1);

namespace Glytos;

/**
 * Webhook signature verification.
 *
 * Matches the server scheme: HMAC-SHA256 over `"{timestamp}.{body}"`, delivered in
 * the `X-Glytos-Signature: t=<timestamp>,v1=<hex>` header.
 */
final class Webhook
{
    /**
     * Return true only if the signature header is valid for the given raw body.
     *
     * Pass the RAW request body exactly as received (do not re-encode it), the
     * `X-Glytos-Signature` header value, and your endpoint secret. The comparison is
     * constant-time, and deliveries older than the tolerance window are rejected to
     * blunt replay attacks (pass `0` to disable the timestamp check).
     */
    public static function verify(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $toleranceSeconds = 300,
    ): bool {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $piece) {
            $pair = explode('=', $piece, 2);
            if (count($pair) === 2) {
                $parts[trim($pair[0])] = trim($pair[1]);
            }
        }

        $timestamp = $parts['t'] ?? null;
        $provided = $parts['v1'] ?? null;
        if ($timestamp === null || $provided === null) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        if (!hash_equals($expected, $provided)) {
            return false;
        }

        if ($toleranceSeconds > 0) {
            if (!ctype_digit($timestamp)) {
                return false;
            }
            if (abs(time() - (int) $timestamp) > $toleranceSeconds) {
                return false;
            }
        }

        return true;
    }
}
