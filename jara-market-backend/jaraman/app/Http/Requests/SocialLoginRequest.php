<?php

namespace App\Http\Requests;

use App\Enums\UserPermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'role' => [
                'nullable',
                'string',
                Rule::in([
                    UserPermissionsEnum::CUSTOMER(),
                    UserPermissionsEnum::VENDOR(),
                ]),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'token.required' => 'The authentication token from the social provider is required.',
            'token.string' => 'The authentication token must be a valid string.',
            'role.in' => 'The role must be either "customer" or "vendor".',
        ];
    }
}
