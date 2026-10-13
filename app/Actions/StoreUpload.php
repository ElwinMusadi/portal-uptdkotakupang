<?php

namespace App\Actions;

use App\Enums\UploadTarget;
use App\Models\Upload;
use App\Models\User;
use finfo;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreUpload
{
    /**
     * Longest side of a stored image, in pixels, as the dashboard resizes.
     */
    public const int MAX_IMAGE_SIDE = 1600;

    /**
     * Largest image the server decodes, in pixels, so decoding stays within
     * memory.
     */
    public const int MAX_IMAGE_PIXELS = 25_000_000;

    /**
     * Store a validated upload for its target (D-028). Images are decoded
     * and re-encoded: turned upright, at most 1600 pixels on their longest
     * side, and without metadata such as the location a photo was taken.
     * Documents are stored as they are. The stored name is random; the
     * original name is kept only for display.
     *
     * @throws ValidationException
     */
    public function handle(User $user, UploadTarget $target, UploadedFile $file): Upload
    {
        $type = self::detectType($file);
        $extension = $target->types()[$type];
        $contents = array_key_exists($type, UploadTarget::IMAGE_TYPES)
            ? $this->reencodeImage($file, $type)
            : (string) file_get_contents($file->getRealPath());

        $upload = new Upload;
        $upload->forceFill([
            'id' => $upload->newUniqueId(),
            'tujuan' => $target,
            'publik' => $target->isPublic(),
            'nama' => $this->displayName($file->getClientOriginalName(), $extension),
            'tipe' => $type,
            'ukuran' => strlen($contents),
            'user_id' => $user->id,
        ]);
        $upload->path = "{$target->value}/".now()->format('Y/m')."/{$upload->id}.{$extension}";

        $upload->disk()->put($upload->path, $contents);

        try {
            $upload->save();
        } catch (Throwable $exception) {
            $upload->disk()->delete($upload->path);

            throw $exception;
        }

        return $upload;
    }

    /**
     * The file's type as its content shows it, whatever its name or the type
     * the browser sent.
     */
    public static function detectType(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        return $path === false ? '' : (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
    }

    /**
     * @throws ValidationException
     */
    private function reencodeImage(UploadedFile $file, string $type): string
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $size = @getimagesizefromstring($contents);

        if ($size === false) {
            throw ValidationException::withMessages(['berkas' => 'Gambar tidak dapat dibaca.']);
        }

        if ($size[0] * $size[1] > self::MAX_IMAGE_PIXELS) {
            throw ValidationException::withMessages(['berkas' => 'Gambar terlalu besar (maksimal 25 megapiksel).']);
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw ValidationException::withMessages(['berkas' => 'Gambar tidak dapat dibaca.']);
        }

        imagepalettetotruecolor($image);
        $image = $this->scale($this->upright($image, $file, $type));
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        match ($type) {
            'image/png' => imagepng($image, null, 6),
            'image/webp' => imagewebp($image, null, 82),
            default => imagejpeg($image, null, 82),
        };

        return (string) ob_get_clean();
    }

    /**
     * Turn a photo the way its camera recorded it, before the record of that
     * is dropped with the rest of the metadata.
     */
    private function upright(GdImage $image, UploadedFile $file, string $type): GdImage
    {
        if ($type !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (@exif_read_data($file->getRealPath()) ?: [])['Orientation'] ?? 1;
        $angle = match ($orientation) {
            3 => 180,
            6 => 270,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    private function scale(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = self::MAX_IMAGE_SIDE / max($width, $height);

        if ($ratio >= 1) {
            return $image;
        }

        return imagescale($image, max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio))) ?: $image;
    }

    /**
     * The original name without folders or control characters, with the
     * extension the file is stored under.
     */
    private function displayName(string $original, string $extension): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', basename(str_replace('\\', '/', $original))) ?? '';
        $base = Str::limit(trim(pathinfo($name, PATHINFO_FILENAME)), 180, '');

        return ($base === '' ? 'berkas' : $base).'.'.$extension;
    }
}
