<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;

    }

    public function rules(): array
    {
        return [
            'identifier' => 'required|string',
            'password' => 'required|string|min:6',
            'remember' => 'sometimes|boolean',
            'mfa_code' => 'sometimes|string|size:6',
        ];
    }

}
