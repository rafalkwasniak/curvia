<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Podaj nazwę.',
            'email.required' => 'Podaj adres email.',
            'email.email' => 'Podaj poprawny adres email.',
            'email.unique' => 'Ten adres email jest już zajęty.',
            'password.min' => 'Hasło musi mieć co najmniej :min znaków.',
            'password.confirmed' => 'Potwierdzenie hasła nie zgadza się.',
        ];
    }
}
