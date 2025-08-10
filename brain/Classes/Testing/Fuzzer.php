<?php
/**
 * Class Fuzzer
 *
 * Generates random test data for fuzz testing applications.
 */
class Fuzzer
{
    protected array $charPool;

    public function __construct()
    {
        $this->charPool = array_merge(
            range('a', 'z'),
            range('A', 'Z'),
            range('0', '9'),
            str_split('!@#$%^&*()_+-=[]{}|;:,.<>?/~')
        );
    }

    /**
     * Generate a random string of given length.
     */
    public function randomString(int $length = 10): string
    {
        $result = '';
        $maxIndex = count($this->charPool) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $this->charPool[random_int(0, $maxIndex)];
        }
        return $result;
    }

    /**
     * Generate an array of random strings.
     */
    public function randomStringArray(int $count = 5, int $length = 10): array
    {
        $output = [];
        for ($i = 0; $i < $count; $i++) {
            $output[] = $this->randomString($length);
        }
        return $output;
    }

    /**
     * Generate random integers within a given range.
     */
    public function randomIntegers(int $count = 5, int $min = 0, int $max = 100): array
    {
        $output = [];
        for ($i = 0; $i < $count; $i++) {
            $output[] = random_int($min, $max);
        }
        return $output;
    }
}
