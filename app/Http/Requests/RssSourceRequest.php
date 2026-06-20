<?php

namespace App\Http\Requests;

use App\Models\RssSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RssSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $source = $this->route('rssSource');

        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => [
                'required', 'string', 'url', 'max:2048',
                Rule::unique('rss_sources', 'url')->ignore($source instanceof RssSource ? $source->id : null),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Podaj nazwę źródła.',
            'url.required' => 'Podaj adres feedu RSS.',
            'url.url' => 'Podaj poprawny adres URL.',
            'url.unique' => 'To źródło jest już na liście.',
        ];
    }
}
