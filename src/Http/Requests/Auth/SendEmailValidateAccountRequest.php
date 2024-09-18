<?php

namespace IsoDoss\TerritorialDivision\Http\Requests\Auth;

use IsoDoss\TerritorialDivision\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class SendEmailValidateAccountRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:strict', 'max:255', Rule::exists('users', 'email')->where(fn(Builder $query) => $query->whereNull('activated_at')->whereNull('verified_at')->whereNull('deleted_at'))],
            'validate_account_url' => ['required', 'url'],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return SendEmailValidateAccountRequest The send email validate account request.
     */
    protected function SendEmailValidateAccountRequest(): SendEmailValidateAccountRequest
    {
        return $this->merge([
            'validate_account_url' => is_null($this->input('validate_account_url')) ? config('app.url') . '/api/v1/auth/validate-account' : $this->input('validate_account_url'),
        ]);
    }
}
