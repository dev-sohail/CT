<?php

namespace App\Domains\SecretsPasswordAnd2FAVault\Services;

class TotpService
{
    public const STEP = 30;

    public function generateSecret(int $bytes = 20): string
    {
        return $this->encodeBase32(random_bytes($bytes));
    }

    public function currentCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return $this->codeAt($secret, intdiv($timestamp, self::STEP));
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $counter = intdiv(time(), self::STEP);
        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->codeAt($secret, $counter + $offset), $code)) {
                return true;
            }
        }
        return false;
    }

    private function codeAt(string $secret, int $counter): string
    {
        $bin = pack('N*', 0).pack('N*', $counter);
        $key = $this->decodeBase32(strtoupper(str_replace(' ', '', $secret)));
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $value = (
            (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function encodeBase32(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        $count = 0;
        $buffer = 0;
        for ($i = 0; $i < strlen($bits); $i++) {
            $buffer = ($buffer << 1) + (int) $bits[$i];
            $count++;
            if ($count === 5) {
                $result .= $alphabet[$buffer];
                $buffer = 0;
                $count = 0;
            }
        }
        if ($count > 0) {
            $result .= $alphabet[($buffer << (5 - $count)) & 0x1F];
        }

        return $result;
    }

    private function decodeBase32(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $map = array_flip(str_split($alphabet));

        $bits = '';
        $len = strlen($input);
        for ($i = 0; $i < $len; $i++) {
            $char = $input[$i];
            if (!isset($map[$char])) {
                continue;
            }
            $bits .= str_pad(decbin($map[$char]), 5, '0', STR_PAD_LEFT);
        }

        $result = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $result .= chr((int) bindec(substr($bits, $i, 8)));
        }

        return $result;
    }
}