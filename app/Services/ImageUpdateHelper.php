<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageUpdateHelper
{
    /**
     * Check if there are actual uploaded files in the images array
     */
    public static function hasNewImages(array $images): bool
    {
        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                return true;
            }
        }
        return false;
    }

    /**
     * Filter only uploaded files from images array
     */
    public static function filterUploadedFiles(array $images): array
    {
        return array_filter($images, function ($image) {
            return $image instanceof UploadedFile;
        });
    }

    /**
     * Check if image is a new uploaded file
     */
    public static function isNewUpload($image): bool
    {
        return $image instanceof UploadedFile;
    }

    /**
     * Prepare image data for update - only process if new uploads exist
     * Also handles URL stripping for existing S3 URLs
     */
    public static function prepareImageUpdate(array &$data, string $imageKey, $existingImages, callable $uploadCallback, callable $deleteCallback): void
    {
        if (!isset($data[$imageKey])) {
            return;
        }

        $images = $data[$imageKey];

        // If images is not array or empty, remove from update data
        if (!is_array($images) || empty($images)) {
            unset($data[$imageKey]);
            return;
        }

        // Check if there are actual new uploads
        if (self::hasNewImages($images)) {
            // Delete existing images if any
            if ($existingImages) {
                $deleteCallback($existingImages);
            }

            // Upload new images
            $data[$imageKey] = $uploadCallback($images);
        } else {
            // No new uploads, check if URLs need to be stripped
            $s3Service = app(\App\Services\S3ImageService::class);
            $processedImages = [];

            foreach ($images as $image) {
                if (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                    // Strip AWS endpoint from URL
                    $processedImages[] = $s3Service->stripAwsEndpoint($image);
                } elseif (is_string($image)) {
                    // Already a path, keep as is
                    $processedImages[] = $image;
                }
            }

            // Update with processed images (URLs stripped to paths)
            if (!empty($processedImages)) {
                $data[$imageKey] = $processedImages;
            } else {
                unset($data[$imageKey]);
            }
        }
    }
}
