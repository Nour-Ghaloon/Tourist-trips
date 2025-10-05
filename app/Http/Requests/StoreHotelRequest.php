<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreHotelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:20',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'contact_info' => 'required|string',
            'rate' => 'required|between:0,5',
            'images'=>'nullable|array',
            'images.*'=>'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
            'caption'=>'nullable|string|max:255',
            'alt_text'=>'nullable|string',
            'city_id'=>'required|exists:cities,id',

        ];
    }
}
