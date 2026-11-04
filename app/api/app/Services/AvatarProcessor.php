<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Turns an uploaded picture into a 256x256 JPEG data URI: EXIF orientation is
 * applied, transparency is flattened on white, and the centre square is kept.
 */
class AvatarProcessor
{
    public const SIZE = 256;

    private const MAX_PIXELS = 25_000_000;

    public function toDataUri(UploadedFile $file): string
    {
        $bytes = (string) file_get_contents($file->getRealPath());
        $dims = @getimagesizefromstring($bytes);

        if ($dims === false || $dims[0] * $dims[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages([
                'avatar' => 'Không thể xử lý ảnh này. Vui lòng chọn ảnh khác.',
            ]);
        }

        $source = @imagecreatefromstring($bytes);

        if (! $source instanceof GdImage) {
            throw ValidationException::withMessages([
                'avatar' => 'Không thể xử lý ảnh này. Vui lòng chọn ảnh khác.',
            ]);
        }

        $source = $this->orient($source, $this->exifOrientation($bytes));

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas, $source,
            0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::SIZE, self::SIZE, $side, $side,
        );

        ob_start();
        imagejpeg($canvas, null, 85);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }

    /**
     * Read the EXIF orientation (1-8) from a JPEG; 1 when absent or not a JPEG.
     * Parsed by hand because the exif extension is not installed.
     */
    private function exifOrientation(string $bytes): int
    {
        $length = strlen($bytes);

        if ($length < 4 || substr($bytes, 0, 2) !== "\xFF\xD8") {
            return 1;
        }

        $pos = 2;

        while ($pos + 4 <= $length && $bytes[$pos] === "\xFF") {
            $marker = ord($bytes[$pos + 1]);

            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }

            $size = unpack('n', substr($bytes, $pos + 2, 2))[1] ?? 0;

            if ($size < 2) {
                break;
            }

            if ($marker === 0xE1 && substr($bytes, $pos + 4, 6) === "Exif\0\0") {
                return $this->tiffOrientation(substr($bytes, $pos + 10, $size - 8));
            }

            $pos += 2 + $size;
        }

        return 1;
    }

    private function tiffOrientation(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }

        $little = substr($tiff, 0, 2) === 'II';

        if (! $little && substr($tiff, 0, 2) !== 'MM') {
            return 1;
        }

        $short = fn (int $offset): ?int => strlen($tiff) >= $offset + 2
            ? unpack($little ? 'v' : 'n', substr($tiff, $offset, 2))[1]
            : null;
        $long = fn (int $offset): ?int => strlen($tiff) >= $offset + 4
            ? unpack($little ? 'V' : 'N', substr($tiff, $offset, 4))[1]
            : null;

        $ifd = $long(4);
        $count = $ifd === null ? null : $short($ifd);

        if ($ifd === null || $count === null) {
            return 1;
        }

        for ($i = 0; $i < $count; $i++) {
            $entry = $ifd + 2 + $i * 12;

            if ($short($entry) === 0x0112) {
                $value = $short($entry + 8);

                return $value !== null && $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }

    private function orient(GdImage $image, int $orientation): GdImage
    {
        return match ($orientation) {
            2 => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, IMG_FLIP_VERTICAL),
            5 => $this->flip($this->rotate($image, 90), IMG_FLIP_VERTICAL),
            6 => $this->rotate($image, -90),
            7 => $this->flip($this->rotate($image, -90), IMG_FLIP_VERTICAL),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function flip(GdImage $image, int $mode): GdImage
    {
        imageflip($image, $mode);

        return $image;
    }

    /** Rotate counter-clockwise by $degrees (a multiple of 90). */
    private function rotate(GdImage $image, int $degrees): GdImage
    {
        return imagerotate($image, $degrees, 0) ?: $image;
    }
}
