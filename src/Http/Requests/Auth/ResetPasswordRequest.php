<?php

namespace IsoDoss\TerritorialDivision\Http\Requests\Auth;

use IsoDoss\TerritorialDivision\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:strict', 'max:255', Rule::exists('users', 'email')->where(fn(Builder $query) => $query->where('role', $this->input('role') ?? 'student')->whereNotNull('activated_at')->whereNotNull('verified_at')->whereNull('deleted_at')), Rule::exists('password_reset_tokens', 'email')->where(fn(Builder $query) => $query->where('role', $this->input('role') ?? 'student')->where('type', 'FORGOT-PASSWORD')->where('token', $this->input('token')))],
            'role' => ['required', 'string', 'max:255', Rule::exists(config('permission.table_names.roles'), 'name')->where(fn(Builder $query) => $query->whereNotNull('activated_at')->whereNull('deleted_at'))],
            'token' => ['required', 'string', 'max:255', Rule::exists('password_reset_tokens', 'token')->where(fn(Builder $query) => $query->where('role', $this->input('role') ?? 'student')->where('type', 'FORGOT-PASSWORD')->where('email', $this->input('email')))],
            'password' => ['required', 'string', 'confirmed',
                Password::min(8) // Définit une longueur minimale de 8 caractères
                ->mixedCase() // Doit contenir des lettres majuscules et minuscules
                ->letters() // Doit contenir au moins une lettre
                ->numbers() // Doit contenir au moins un chiffre
                ->symbols() // Doit contenir au moins un symbole
                ->uncompromised(), // Vérifie que le mot de passe n'a pas été compromis dans une fuite de données
            ],
            'password_confirmation' => ['required', 'string', 'max:255', 'same:password',
                Password::min(8) // Définit une longueur minimale de 8 caractères
                ->mixedCase() // Doit contenir des lettres majuscules et minuscules
                ->letters() // Doit contenir au moins une lettre
                ->numbers() // Doit contenir au moins un chiffre
                ->symbols() // Doit contenir au moins un symbole
                ->uncompromised(), // Vérifie que le mot de passe n'a pas été compromis dans une fuite de données
            ],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return ResetPasswordRequest The validate account request.
     */
    protected function prepareForValidation(): ResetPasswordRequest
    {
        return $this->merge([
            'email' => is_null($this->input('email')) ? $this->route('email') : $this->input('email'),
            'role' => is_null($this->input('role')) ? $this->route('role') : $this->input('role'),
            'token' => is_null($this->input('token')) ? $this->route('token') : $this->input('token'),
        ]);
    }
}
