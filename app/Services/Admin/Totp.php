<?php

namespace App\Services\Admin;

class Totp
{
    public function newSecret(): string
    {
        $bytes = random_bytes(20);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) === 5) {
                $secret .= $alphabet[bindec($chunk)];
            }
        }

        return $secret;
    }

    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = (int) floor(($timestamp ?? time()) / 30);
        for ($window = -1; $window <= 1; $window++) {
            if (hash_equals($this->code($secret, $counter + $window), $code)) {
                return true;
            }
        }

        return false;
    }

    public function code(string $secret, int $counter): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bits = 0;
        $key = '';
        foreach (str_split($secret) as $character) {
            $value = strpos($alphabet, $character);
            if ($value === false) {
                return '';
            }
            $buffer = ($buffer << 5) | $value;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $key .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        $binaryCounter = pack('N2', intdiv($counter, 0x100000000), $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }
}
