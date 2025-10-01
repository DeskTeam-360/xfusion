<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;

class FileUploadHelper
{
    /**
     * Upload file without using Laravel's storage system to avoid finfo dependency
     */
    public static function uploadFile(UploadedFile $file, string $directory, string $filename): string
    {
        // Create directory if it doesn't exist
        $fullDirectory = storage_path('app/public/' . $directory);
        if (!file_exists($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }
        
        // Move file to destination
        $file->move($fullDirectory, $filename);
        
        // Return relative path
        return $directory . '/' . $filename;
    }
    
    /**
     * Generate unique filename
     */
    public static function generateFilename(string $originalName, int $userId): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return time() . '_' . $userId . '_' . $originalName;
    }
    
    /**
     * Validate file extension
     */
    public static function validateFileExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions);
    }
}
