<?php

namespace IsoDoss\TerritorialDivision\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class GroupActionRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:enable,disable,delete,export,confirm'],
            'data' => ['required', 'array'],
        ];
    }
}
