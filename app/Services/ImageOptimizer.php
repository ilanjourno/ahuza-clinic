<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;

/**
 * Downscales and recompresses uploaded JPEG, PNG and WebP images in place.
 * A file that was not resized is only replaced when re-encoding makes it smaller.
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1600;

    public const QUALITY = 82;

    public function optimize(UploadedFile $upload): void
    {
        $path = $upload->getRealPath();
        $mime = $upload->getMimeType();
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if ($image === false) {
            return;
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyExifOrientation($image, $path);
        }

        $resized = imagesx($image) > self::MAX_WIDTH;
        if ($resized) {
            $image = imagescale($image, self::MAX_WIDTH);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $optimized = tempnam(sys_get_temp_dir(), 'img');
        match ($mime) {
            'image/jpeg' => imagejpeg($image, $optimized, self::QUALITY),
            'image/png' => imagepng($image, $optimized, 9),
            'image/webp' => imagewebp($image, $optimized, self::QUALITY),
        };

        if ($resized || filesize($optimized) < filesize($path)) {
            rename($optimized, $path);
        } else {
            unlink($optimized);
        }

        clearstatcache(true, $path);
    }

    // GD drops EXIF data, so phone photos must be rotated before re-encoding.
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        $orientation = @exif_read_data($path)['Orientation'] ?? 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
