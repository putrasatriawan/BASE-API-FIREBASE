<?php

namespace App\Services;

use App\Exceptions\PublicException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class S3ImageService
{
    private string $disk = 's3';
    private string $bucket;
    private array $allowedTypes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.s3.bucket');
    }

    /**
     * Upload single file to S3
     */
    public function uploadFile(UploadedFile $file, string $folder = 'images'): string
    {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::random(10) . '.' . $extension;
            $path = $folder . '/' . $filename;

            // Upload to S3
            $uploaded = Storage::disk($this->disk)->putFileAs($folder, $file, $filename, 'public');

            if (!$uploaded) {
                throw $e;
            }

            return $uploaded;
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('S3 file upload failed', [
                'error' => $e->getMessage(),
                'folder' => $folder,
                'file_name' => $file->getClientOriginalName()
            ]);

            throw $e;
        }
    }

    /**
     * Upload multiple files to S3
     */
    public function uploadMultipleFiles(array $files, string $folder = 'images'): array
    {
        $filePaths = [];

        foreach ($files as $index => $file) {
            try {
                if ($file instanceof UploadedFile) {
                    $filePath = $this->uploadFile($file, $folder);
                    $filePaths[] = $filePath;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to process uploaded file', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other files, don't fail the entire process
            }
        }

        return $filePaths;
    }

    /**
     * Upload single base64 image to S3 (backward compatibility)
     */
    public function uploadBase64Image(string $base64String, string $folder = 'images'): string
    {
        try {
            // Extract image info from base64 string
            if (!preg_match('/^data:image\/([^;]+);base64,(.+)$/', $base64String, $matches)) {
                throw new \Exception('Format base64 image tidak valid');
            }

            $imageType = $matches[1];
            $imageData = $matches[2];

            // Validate image type
            if (!in_array(strtolower($imageType), $this->allowedTypes)) {
                throw new \Exception('Tipe gambar tidak didukung. Gunakan: ' . implode(', ', $this->allowedTypes));
            }

            // Decode base64
            $decodedImage = base64_decode($imageData);
            if ($decodedImage === false) {
                throw $e;
            }

            // Check file size
            if (strlen($decodedImage) > $this->maxFileSize) {
                throw new \Exception('Ukuran gambar terlalu besar. Maksimal 5MB');
            }

            // Generate unique filename
            $extension = $imageType === 'jpeg' ? 'jpg' : $imageType;
            $filename = time() . '_' . Str::random(10) . '.' . $extension;
            $path = $folder . '/' . $filename;

            // Upload to S3
            $uploaded = Storage::disk($this->disk)->put($path, $decodedImage, 'public');

            if (!$uploaded) {
                throw $e;
            }

            return $path;
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('S3 base64 image upload failed', [
                'error' => $e->getMessage(),
                'folder' => $folder
            ]);

            throw $e;
        }
    }

    /**
     * Upload multiple base64 images to S3 (backward compatibility)
     */
    public function uploadMultipleBase64Images(array $base64Images, string $folder = 'images'): array
    {
        $imagePaths = [];

        foreach ($base64Images as $index => $base64String) {
            try {
                $imagePath = $this->uploadBase64Image($base64String, $folder);
                $imagePaths[] = $imagePath;
            } catch (\Throwable $e) {
                Log::error('Failed to process base64 image', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other images, don't fail the entire process
            }
        }

        return $imagePaths;
    }

    /**
     * Delete image from S3
     */
    public function deleteImage(string $imagePath): bool
    {
        try {
            if (Storage::disk($this->disk)->exists($imagePath)) {
                return Storage::disk($this->disk)->delete($imagePath);
            }
            return true; // File doesn't exist, consider as deleted
        } catch (\Throwable $e) {
            Log::warning('Failed to delete S3 image', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete multiple images from S3
     */
    public function deleteMultipleImages(array $imagePaths): array
    {
        $results = [];

        foreach ($imagePaths as $imagePath) {
            $results[$imagePath] = $this->deleteImage($imagePath);
        }

        return $results;
    }

    /**
     * Get full S3 URL for image
     */
    public function getImageUrl(string $imagePath): string
    {
        try {
            return Storage::disk($this->disk)->url($imagePath);
        } catch (\Throwable $e) {
            Log::warning('Failed to get S3 image URL', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Get multiple image URLs
     */
    public function getMultipleImageUrls(array $imagePaths): array
    {
        return array_map(function ($imagePath) {
            return $this->getImageUrl($imagePath);
        }, $imagePaths);
    }

    /**
     * Check if image exists in S3
     */
    public function imageExists(string $imagePath): bool
    {
        try {
            return Storage::disk($this->disk)->exists($imagePath);
        } catch (\Throwable $e) {
            Log::warning('Failed to check S3 image existence', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Strip AWS endpoint from full URL and return only the path
     * Example: https://bucket.s3.region.amazonaws.com/products/image.jpg -> products/image.jpg
     */
    public function stripAwsEndpoint(string $url): string
    {
        $awsEndpoint = config('filesystems.disks.s3.url');

        // If URL starts with AWS endpoint, remove it
        if (str_starts_with($url, $awsEndpoint)) {
            return ltrim(str_replace($awsEndpoint, '', $url), '/');
        }

        // If it's already a path (not a full URL), return as is
        return $url;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception('File tidak valid');
        }

        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception('Ukuran file terlalu besar. Maksimal 7MB');
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedTypes)) {
            throw new \Exception('Tipe file tidak didukung. Gunakan: ' . implode(', ', $this->allowedTypes));
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new \Exception('MIME type file tidak didukung');
        }
    }
}
