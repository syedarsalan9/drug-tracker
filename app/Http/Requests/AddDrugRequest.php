<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddDrugRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rxcui' => 'required|string'
        ];
    }

    public function messages()
    {
        return [
            'rxcui.required' => 'RXCUI is required to add a medication'
        ];
    }
}