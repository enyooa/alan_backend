<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBasicProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize():bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name_of_products' => 'required|string',
            'description' => 'nullable|string',
            'country' => 'nullable|string',
            'type' => 'nullable|string',
            'brutto' => 'required|numeric',
            'netto' => 'required|numeric',
            'photo_product' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
