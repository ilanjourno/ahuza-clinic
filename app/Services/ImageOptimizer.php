<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;

/**
 * Downscales and recompresses uploaded JPEG, PNG and WebP images in place.
 * A file that was not resized is only replaced when re-encoding makes it smaller.
 * Best effort: any failure leaves the upload exactly as received.
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1600;

    public const QUALITY = 82;

    private const GD_TYPES = [
        'image/jpeg' => IMG_JPG,
        'image/png' => IMG_PNG,
        'image/webp' => IMG_WEBP,
    ];

    public function optimize(UploadedFile $upload): void
    {
        $optimized = null;

        try {
            $path = $upload->getRealPath();
            $mime = $upload->getMimeType();

            if (! $path || ! isset(self::GD_TYPES[$mime]) || ! (imagetypes() & self::GD_TYPES[$mime])) {
                return;
            }

            $image = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($path),
                'image/png' => @imagecreatefrompng($path),
                'image/webp' => @imagecreatefromwebp($path),
            };

            if ($image === false) {
                return;
            }

            if ($mime === 'image/jpeg') {
                $image = $this->applyExifOrientation($image, $path);
            }

            $resized = false;
            if (imagesx($image) > self::MAX_WIDTH) {
                $scaled = imagescale($image, self::MAX_WIDTH);
                if ($scaled !== false) {
                    $image = $scaled;
                    $resized = true;
                }
            }

            imagealphablending($image, false);
            imagesavealpha($image, true);

            $optimized = tempnam(sys_get_temp_dir(), 'img');
            if ($optimized === false) {
                return;
            }

            $encoded = match ($mime) {
                'image/jpeg' => imagejpeg($image, $optimized, self::QUALITY),
                'image/png' => imagepng($image, $optimized, 9),
                'image/webp' => imagewebp($image, $optimized, self::QUALITY),
            };

            clearstatcache(true, $optimized);
            $newSize = $encoded ? filesize($optimized) : false;

            if (! $newSize) {
                return;
            }

            if ($resized || $newSize < filesize($path)) {
                if (rename($optimized, $path)) {
                    $optimized = null;
                }
            }

            clearstatcache(true, $path);
        } catch (\Throwable $e) {
            report($e);
        } finally {
            if (is_string($optimized) && file_exists($optimized)) {
                @unlink($optimized);
            }
        }
    }

    // GD drops EXIF data, so phone photos must be rotated before re-encoding.
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        $orientation = @exif_read_data($path)['Orientation'] ?? 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
