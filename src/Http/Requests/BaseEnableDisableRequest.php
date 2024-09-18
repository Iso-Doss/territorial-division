<?php

namespace IsoDoss\TerritorialDivision\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class BaseEnableDisableRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'new_status' => ['required', 'string', 'in:enable,disable'],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return BaseEnableDisableRequest The user role enable or disable request.
     */
    protected function prepareForValidation(): BaseEnableDisableRequest
    {
        return $this->merge([
            'new_status' => is_null($this->input('new_status')) ? $this->route('newStatus') : $this->input('new_status'),
        ]);
    }
}
