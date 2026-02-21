<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsSearchRequest extends FormRequest
{
    /**
     * All users are allowed to perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the news search/filter endpoint.
     */
    public function rules(): array
    {
        return [
            'date'   => ['required', 'date_format:Y-m-d'],

            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date'   => 'date',
            'search' => 'search query',
        ];
    }
}
