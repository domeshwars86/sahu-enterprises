<?php

namespace Tests\Unit;

use App\Services\Admin\Totp;
use PHPUnit\Framework\TestCase;

class AdminTotpTest extends TestCase
{
    public function test_totp_uses_rfc6238_sha1_six_digit_code_and_validates_window(): void
    {
        $totp = new Totp;
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $timestamp = 30;
        $code = $totp->code($secret, 1);

        $this->assertSame('287082', $code);
        $this->assertTrue($totp->verify($secret, $code, $timestamp));
        $this->assertFalse($totp->verify($secret, '000000', $timestamp));
    }
}
