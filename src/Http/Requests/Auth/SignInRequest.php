<?php

namespace IsoDoss\TerritorialDivision\Http\Requests\Auth;

use IsoDoss\TerritorialDivision\Http\Requests\BaseRequest;
use IsoDoss\TerritorialDivision\Http\Resources\SendApiResponse;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SignInRequest extends BaseRequest
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
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (!Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            if ($this->isApiRequest()) {
                $response = (new SendApiResponse(false, __('messages.bad-request'), $this->validator->errors(), [], $this->all(), [], 401))->response();
                throw (new ValidationException($this->validator, $response))->status(401);
            } else {
                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }

        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')) . '|' . Str::lower($this->input('role')) . '|' . $this->ip());
    }
}
