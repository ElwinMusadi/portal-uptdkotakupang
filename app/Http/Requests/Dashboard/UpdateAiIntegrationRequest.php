<?php

namespace App\Http\Requests\Dashboard;

use App\Support\AiSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Pengaturan › Integrasi AI as an administrator saves it (D-085). An empty
 * key keeps the one saved before; "hapusKunci" removes it, so the server's
 * .env key is used again.
 */
class UpdateAiIntegrationRequest extends FormRequest
{
    use AiIntegrationRules;

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through SettingPolicy.
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
        return [...$this->aiIntegrationRules(), 'hapusKunci' => ['sometimes', 'boolean']];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->keepKeyOnItsHost($validator, $this->boolean('hapusKunci'))];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->aiIntegrationMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->aiIntegrationAttributes();
    }

    /**
     * The document to store: the new key encrypted, the saved one kept, or
     * none when it is removed.
     *
     * @return array{baseUrl: string, model: string, kunci: ?string}
     */
    public function aiIntegration(): array
    {
        $key = $this->newKey();

        return [
            'baseUrl' => (string) $this->validated('baseUrl'),
            'model' => (string) $this->validated('model'),
            'kunci' => match (true) {
                $key !== null => AiSettings::encrypt($key),
                $this->boolean('hapusKunci') => null,
                default => AiSettings::stored()['kunci'] ?? null,
            },
        ];
    }
}
