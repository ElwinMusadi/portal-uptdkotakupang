<?php

namespace App\Http\Requests\Dashboard;

use App\Actions\StoreUpload;
use App\Enums\UploadTarget;
use App\Models\Upload;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * A file uploaded from the dashboard (D-028), with the target it is for.
 * The type is read from the file's content, never from its name, and the
 * size limit follows that type: 5 MB for images, 10 MB for documents.
 */
class StoreUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request: managers
     * of the target's module, or every employee for attendance photos. An
     * unknown target is left to validation.
     */
    public function authorize(): bool
    {
        $target = $this->target();

        if ($target !== null) {
            Gate::authorize('create', [Upload::class, $target]);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $target = $this->target();
        $file = ['required', 'file'];

        if ($target !== null) {
            $file[] = function (string $attribute, mixed $value, Closure $fail) use ($target): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $type = StoreUpload::detectType($value);

                if (! array_key_exists($type, $target->types())) {
                    $fail("Format berkas harus {$target->formats()}.");

                    return;
                }

                $image = array_key_exists($type, UploadTarget::IMAGE_TYPES);
                $limit = $image ? UploadTarget::MAX_IMAGE_KILOBYTES : UploadTarget::MAX_DOCUMENT_KILOBYTES;

                if ($value->getSize() > $limit * 1024) {
                    $fail($image ? 'Ukuran gambar maksimal 5 MB.' : 'Ukuran dokumen maksimal 10 MB.');
                }
            };
        }

        return [
            'modul' => ['required', 'string', Rule::enum(UploadTarget::class)],
            'berkas' => $file,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'modul.required' => 'Tujuan unggahan wajib diisi.',
            'modul.string' => 'Tujuan unggahan tidak dikenal.',
            'modul.enum' => 'Tujuan unggahan tidak dikenal.',
            'berkas.required' => 'Pilih berkas yang akan diunggah.',
            'berkas.file' => 'Berkas gagal diunggah. Coba lagi.',
            'berkas.uploaded' => 'Berkas gagal diunggah. Coba lagi.',
        ];
    }

    /**
     * The target the file is uploaded for, when it is a known one.
     */
    public function target(): ?UploadTarget
    {
        $value = $this->input('modul');

        return is_string($value) ? UploadTarget::tryFrom($value) : null;
    }
}
