<?php

namespace IsoDoss\TerritorialDivision\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class BaseFilterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'with' => ['nullable', 'array'],
            'status' => ['nullable', 'in:enable,disable,delete'],
            'number_per_page' => ['nullable', 'numeric'],
            'order_by.column' => ['nullable', 'string'],
            'order_by.direction' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return BaseRequest The base request.
     */
    protected function prepareForValidation(): BaseRequest
    {
        return $this->merge([
            'with' => is_null($this->input('with')) ? [] : $this->input('with'),
            'number_per_page' => is_null($this->input('number_per_page')) ? 10 : $this->input('number_per_page'),
            'order_by.direction' => is_null($this->input('order_by.direction')) ? 'desc' : $this->input('order_by.direction'),
        ]);
    }
}
