<?php
/**
 * Class Captcha
 *
 * Generates and validates CAPTCHA codes to prevent automated form submissions.
 */
class Captcha
{
    protected string $code;
    protected int $length;
    protected string $sessionKey = '_captcha_code';

    public function __construct(int $length = 6)
    {
        $this->length = $length;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Generate a random CAPTCHA code and store it in session.
     */
    public function generateCode(): string
    {
        $this->code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, $this->length);
        $_SESSION[$this->sessionKey] = $this->code;
        return $this->code;
    }

    /**
     * Render the CAPTCHA image.
     */
    public function renderImage(): void
    {
        if (!$this->code) {
            $this->generateCode();
        }

        header('Content-Type: image/png');
        $image = imagecreate(150, 50);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $noiseColor = imagecolorallocate($image, 100, 120, 180);

        // Add noise
        for ($i = 0; $i < 50; $i++) {
            imageellipse($image, rand(0, 150), rand(0, 50), 1, 1, $noiseColor);
        }

        // Add text
        imagestring($image, 5, 35, 18, $this->code, $textColor);

        imagepng($image);
        imagedestroy($image);
    }

    /**
     * Validate a given CAPTCHA input.
     */
    public function validate(string $input): bool
    {
        return isset($_SESSION[$this->sessionKey]) && strtoupper($input) === $_SESSION[$this->sessionKey];
    }
}
