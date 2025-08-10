<?php
/**
 * Class Uploader
 *
 * Handles file uploads with validation and storage capabilities.
 */
class Uploader
{
    protected string $uploadDir;
    protected array $allowedTypes;
    protected int $maxSize;

    public function __construct(string $uploadDir, array $allowedTypes = [], int $maxSize = 5242880)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a file from the $_FILES array.
     */
    public function upload(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        if (!empty($this->allowedTypes) && !in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('Invalid file type: ' . $file['type']);
        }

        if ($file['size'] > $this->maxSize) {
            throw new Exception('File size exceeds limit: ' . $file['size']);
        }

        $destination = $this->uploadDir . '/' . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file.');
        }

        return $destination;
    }

    /**
     * Set allowed MIME types.
     */
    public function setAllowedTypes(array $types): void
    {
        $this->allowedTypes = $types;
    }

    /**
     * Set maximum allowed file size.
     */
    public function setMaxSize(int $size): void
    {
        $this->maxSize = $size;
    }
}
