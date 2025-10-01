<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload file to S3 and return public URL
     */
    public function uploadToS3(UploadedFile $file, string $folder = 'uploads'): string
    {
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Upload to S3
        $path = $file->storeAs($folder, $filename, 's3');
        
        // Return public URL
        return Storage::disk('s3')->url($path);
    }

    /**
     * Upload category icon to S3
     */
    public function uploadCategoryIcon(UploadedFile $file): string
    {
        return $this->uploadToS3($file, 'categories/icons');
    }

    /**
     * Delete file from S3
     */
    public function deleteFromS3(string $url): bool
    {
        try {
            // Extract path from URL
            $path = parse_url($url, PHP_URL_PATH);
            $path = ltrim($path, '/');
            
            return Storage::disk('s3')->delete($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate image file
     */
    public function validateImage(UploadedFile $file): array
    {
        $errors = [];

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'File must be an image (JPEG, PNG, GIF, or WebP)';
        }

        // Check file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'File size must not exceed 5MB';
        }

        return $errors;
    }
}
