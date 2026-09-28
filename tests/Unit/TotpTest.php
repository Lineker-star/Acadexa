<?php

namespace Tests\Unit;

use App\Services\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    /** RFC 6238 appendix B test vector (SHA-1 seed "12345678901234567890"), last 6 digits. */
    public function test_matches_rfc_6238_vectors(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // base32 of "12345678901234567890"
        $totp = new Totp();

        $this->assertSame('287082', $totp->code($secret, 59));
        $this->assertSame('081804', $totp->code($secret, 1111111109));
        $this->assertSame('005924', $totp->code($secret, 1234567890));
        $this->assertSame('279037', $totp->code($secret, 2000000000));
    }

    public function test_verify_accepts_current_code_and_rejects_others(): void
    {
        $totp = new Totp();
        $secret = $totp->generateSecret();

        $this->assertTrue($totp->verify($secret, $totp->code($secret)));
        $this->assertTrue($totp->verify($secret, substr($totp->code($secret), 0, 3) . ' ' . substr($totp->code($secret), 3)));
        $this->assertFalse($totp->verify($secret, '12345'));
        $this->assertFalse($totp->verify($secret, $totp->code($secret, time() - 300)));
    }
}
