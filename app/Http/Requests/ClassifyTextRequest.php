<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassifyTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }
}
