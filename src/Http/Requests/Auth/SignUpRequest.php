<?php

namespace IsoDoss\TerritorialDivision\Http\Requests\Auth;

use IsoDoss\TerritorialDivision\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SignUpRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:255', Rule::exists(config('permission.table_names.roles'), 'name')->where(fn(Builder $query) => $query->whereNotNull('activated_at')->whereNull('deleted_at'))],
            'site_id' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->input('role') !== null && $this->input('role') == 'secretary'), Rule::exists('users', 'id')->where(fn(Builder $query) => $query->whereRole('site')->whereNotNull('activated_at')->whereNull('deleted_at'))],
            'site_code' => ['nullable', 'string', 'unique:users,site_code', Rule::requiredIf($this->input('role') !== null && $this->input('role') == 'site')],
            'site_name' => ['nullable', 'string', 'unique:users,site_code', Rule::requiredIf($this->input('role') !== null && $this->input('role') == 'site')],
            'teacher_subjects' => ['nullable', Rule::requiredIf($this->input('role') !== null && $this->input('role') == 'teacher'), 'array'],
            'email' => ['required', 'string', 'email:strict', 'max:255', 'unique:users,email'],
            'country_id' => ['nullable', Rule::requiredIf($this->input('role') !== null && $this->input('role') == 'site'), Rule::exists('countries', 'id')->where(fn(Builder $query) => $query->whereNotNull('activated_at')->whereNull('deleted_at'))],
            'has_default_password' => ['nullable', 'boolean'],
            'password' => ['nullable', Rule::requiredIf((($this->input('has_default_password') !== null && !$this->input('has_default_password')) || $this->input('has_default_password') == null)), 'string', 'confirmed',
                Password::min(8) // Définit une longueur minimale de 8 caractères
                ->mixedCase() // Doit contenir des lettres majuscules et minuscules
                ->letters() // Doit contenir au moins une lettre
                ->numbers() // Doit contenir au moins un chiffre
                ->symbols() // Doit contenir au moins un symbole
                ->uncompromised(), // Vérifie que le mot de passe n'a pas été compromis dans une fuite de données
            ],
            'password_confirmation' => ['nullable', Rule::requiredIf((($this->input('has_default_password') !== null && !$this->input('has_default_password')) || $this->input('has_default_password') == null)), 'string', 'same:password',
                Password::min(8) // Définit une longueur minimale de 8 caractères
                ->mixedCase() // Doit contenir des lettres majuscules et minuscules
                ->letters() // Doit contenir au moins une lettre
                ->numbers() // Doit contenir au moins un chiffre
                ->symbols() // Doit contenir au moins un symbole
                ->uncompromised(), // Vérifie que le mot de passe n'a pas été compromis dans une fuite de données
            ],
            'terms_condition' => ['required', 'boolean', 'in:1,true'],
            'validate_account_url' => ['nullable', Rule::requiredIf($this->input('role') !== null && $this->input('role') == 'student'), 'url'],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return SignUpRequest The sign-up request.
     */
    protected function prepareForValidation(): SignUpRequest
    {
        return $this->merge([
            'validate_account_url' => is_null($this->input('validate_account_url')) ? config('app.url') . '/api/v1/auth/validate-account' : $this->input('validate_account_url'),
            'site_code' => !is_null($this->input('site_code')) ? strtoupper($this->input('site_code')) : $this->input('site_code'),
        ]);
    }
}
