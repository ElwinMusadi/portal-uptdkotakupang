<?php

namespace App\Http\Requests\Dashboard;

use App\Actions\ListRevenueCounters;
use App\Actions\ReadStsFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * An STS file and the counter it is for (D-047), for the preview and the
 * import. The file is read while validating, so a file that is not an STS
 * report is answered like any other invalid field. It is never stored.
 */
class StoreRevenueImportRequest extends FormRequest
{
    /**
     * Largest STS file accepted, in kilobytes; a month of the office counter
     * is about 4.3 MB.
     */
    public const int MAX_KILOBYTES = 10240;

    /**
     * @var list<array<string, int|string>>
     */
    private array $transactions = [];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through RevenueImportPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'berkas' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'extensions:xls,htm,html'],
            'loket' => ['required', 'string', Rule::in(array_column(app(ListRevenueCounters::class)->handle(), 'kunci'))],
        ];
    }

    /**
     * Read the file once the fields are valid.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                try {
                    $this->transactions = app(ReadStsFile::class)->handle((string) file_get_contents($this->file('berkas')->getRealPath()));
                } catch (ValidationException $exception) {
                    foreach ($exception->errors() as $field => $messages) {
                        $validator->errors()->add($field, $messages[0]);
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih berkas STS yang akan diimpor.',
            'berkas.file' => 'Berkas gagal diunggah. Coba lagi.',
            'berkas.uploaded' => 'Berkas gagal diunggah. Ukurannya mungkin melebihi batas server.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
            'berkas.extensions' => 'Pilih berkas STS berformat .xls hasil ekspor sistem BPAD.',
            'loket.required' => 'Pilih loket asal berkas ini.',
            'loket.string' => 'Loket tidak dikenal.',
            'loket.in' => 'Loket tidak dikenal.',
        ];
    }

    /**
     * The counter the file is for.
     *
     * @return array{kunci: string, nama: string, jenis: string}
     */
    public function counter(): array
    {
        return app(ListRevenueCounters::class)->find($this->validated('loket'));
    }

    /**
     * The file's name as uploaded.
     */
    public function fileName(): string
    {
        /** @var UploadedFile $file */
        $file = $this->file('berkas');

        return $file->getClientOriginalName();
    }

    /**
     * The transactions read from the file.
     *
     * @return list<array<string, int|string>>
     */
    public function transactions(): array
    {
        return $this->transactions;
    }
}
