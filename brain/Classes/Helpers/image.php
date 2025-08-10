<?php
/**
 * Class Image
 *
 * Utility class for basic image manipulation.
 */
class Image
{
    /**
     * Get image dimensions.
     */
    public static function dimensions(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }
        $info = getimagesize($path);
        return $info ? ['width' => $info[0], 'height' => $info[1]] : null;
    }

    /**
     * Get image type.
     */
    public static function type(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        $info = getimagesize($path);
        return $info ? image_type_to_mime_type($info[2]) : null;
    }

    /**
     * Resize an image and save it to a new file.
     */
    public static function resize(string $src, string $dest, int $newWidth, int $newHeight): bool
    {
        if (!file_exists($src)) {
            return false;
        }

        [$width, $height, $type] = getimagesize($src);
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($src),
            IMAGETYPE_PNG => imagecreatefrompng($src),
            IMAGETYPE_GIF => imagecreatefromgif($src),
            default => null
        };

        if (!$image) return false;

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $result = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($resized, $dest),
            IMAGETYPE_PNG => imagepng($resized, $dest),
            IMAGETYPE_GIF => imagegif($resized, $dest),
            default => false
        };

        imagedestroy($image);
        imagedestroy($resized);

        return $result;
    }
}
