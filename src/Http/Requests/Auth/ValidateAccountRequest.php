<?php

namespace IsoDoss\TerritorialDivision\Http\Requests\Auth;

use IsoDoss\TerritorialDivision\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class ValidateAccountRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:strict', 'max:255', Rule::exists('users', 'email')->where(fn(Builder $query) => $query->where('role', $this->input('role') ?? 'student')->whereNull('activated_at')->whereNull('verified_at')->whereNull('deleted_at')), Rule::exists('password_reset_tokens', 'email')->where(fn(Builder $query) => $query->where('role', $this->input('role') ?? 'student')->where('type', 'VALIDATE-ACCOUNT')->where('token', $this->input('token')))],
            'role' => ['required', 'string', 'max:255', 'in:student'],
            'token' => ['required', 'string', 'max:255', Rule::exists('password_reset_tokens', 'token')->where(fn(Builder $query) => $query->where('role', $this->input('role') ?? 'student')->where('type', 'VALIDATE-ACCOUNT')->where('email', $this->input('email')))],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return ValidateAccountRequest The validate account request.
     */
    protected function prepareForValidation(): ValidateAccountRequest
    {
        return $this->merge([
            'email' => is_null($this->input('email')) ? $this->route('email') : $this->input('email'),
            'role' => is_null($this->input('role')) ? $this->route('role') : $this->input('role'),
            'token' => is_null($this->input('token')) ? $this->route('token') : $this->input('token'),
        ]);
    }
}
