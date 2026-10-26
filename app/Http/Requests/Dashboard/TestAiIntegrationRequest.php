<?php

namespace App\Http\Requests\Dashboard;

use App\Support\AiConnection;
use App\Support\AiSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A connection test of Pengaturan › Integrasi AI with the values in the form,
 * before or after they are saved (D-085). An empty key tests the key in use.
 */
class TestAiIntegrationRequest extends FormRequest
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
        return $this->aiIntegrationRules();
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->keepKeyOnItsHost($validator)];
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
     * The connection to test.
     */
    public function connection(): AiConnection
    {
        $current = AiSettings::connection();
        $key = $this->newKey();

        return new AiConnection(
            baseUrl: (string) $this->validated('baseUrl'),
            model: (string) $this->validated('model'),
            key: $key ?? $current->key,
            keySource: $key !== null ? 'formulir' : $current->keySource,
        );
    }
}
