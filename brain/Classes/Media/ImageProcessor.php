<?php
/**
 * Class ImageProcessor
 *
 * Provides basic image manipulation functions like resizing, cropping, and saving.
 */
class ImageProcessor
{
    protected $image;
    protected string $type;

    /**
     * Load an image from a file.
     */
    public function load(string $filename): void
    {
        [$width, $height, $type] = getimagesize($filename);
        $this->type = image_type_to_mime_type($type);

        switch ($this->type) {
            case 'image/jpeg':
                $this->image = imagecreatefromjpeg($filename);
                break;
            case 'image/png':
                $this->image = imagecreatefrompng($filename);
                break;
            case 'image/gif':
                $this->image = imagecreatefromgif($filename);
                break;
            default:
                throw new Exception('Unsupported image type: ' . $this->type);
        }
    }

    /**
     * Resize the image to the given width and height.
     */
    public function resize(int $width, int $height): void
    {
        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $width, $height, imagesx($this->image), imagesy($this->image));
        $this->image = $newImage;
    }

    /**
     * Crop the image to the given dimensions.
     */
    public function crop(int $x, int $y, int $width, int $height): void
    {
        $this->image = imagecrop($this->image, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);
    }

    /**
     * Save the image to a file.
     */
    public function save(string $filename, int $quality = 90): void
    {
        switch ($this->type) {
            case 'image/jpeg':
                imagejpeg($this->image, $filename, $quality);
                break;
            case 'image/png':
                imagepng($this->image, $filename);
                break;
            case 'image/gif':
                imagegif($this->image, $filename);
                break;
        }
    }

    /**
     * Free image memory.
     */
    public function destroy(): void
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }
}
