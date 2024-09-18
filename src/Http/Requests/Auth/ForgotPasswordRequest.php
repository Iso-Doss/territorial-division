<?php

namespace IsoDoss\TerritorialDivision\Http\Requests\Auth;

use IsoDoss\TerritorialDivision\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:strict', 'max:255', Rule::exists('users', 'email')->where(fn(Builder $query) => $query->whereNotNull('activated_at')->whereNotNull('verified_at')->whereNull('deleted_at'))],
            'reset_password_url' => ['required', 'url'],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return ForgotPasswordRequest The forgot password request.
     */
    protected function prepareForValidation(): ForgotPasswordRequest
    {
        return $this->merge([
            'reset_password_url' => is_null($this->input('reset_password_url')) ? config('app.url') . '/api/v1/auth/password/reset' : $this->input('reset_password_url'),
        ]);
    }
}
