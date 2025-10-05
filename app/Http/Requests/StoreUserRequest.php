<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|password|max:10|min:8|confirmed',
            'role'=>'required|in:admin,user,driver,guide,hotel,restaurant',
        ];
    }
    public function messages()
    {   return [
        'name.string' => 'يرجى ادخال الاسم بشكل صحيح',
        'password.confirmed'=>' كلمة المرور غير متطابقة يرجى ادخالها بشكل صحيح',

    ];
    }
}
