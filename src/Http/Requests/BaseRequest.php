<?php

namespace IsoDoss\TerritorialDivision\Http\Requests;

use IsoDoss\TerritorialDivision\Http\Controllers\Controller;
use IsoDoss\TerritorialDivision\Http\Resources\SendApiResponse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator The validator.
     *
     * @throws ValidationException The validation exception.
     */
    public function failedValidation(Validator $validator): void
    {
        $exception = $validator->getException();
        if ($this->isApiRequest()) {
            $apiResponse = new SendApiResponse(false, Controller::API_DEFAULT_ERROR_FIELDS_MESSAGE, $validator->errors(), [], $this->all(), [], 422);
            $response = $apiResponse->response();
            throw (new $exception($validator, $response))->status(422);
        } else {
            throw (new $exception($validator))
                ->errorBag($this->errorBag)
                ->redirectTo($this->getRedirectUrl());
        }
    }

    /**
     * Is api request.
     */
    public function isApiRequest(): bool
    {
        return count(explode('/api/', $this->getRequestUri())) >= 2;
    }

    /**
     * Get table rules.
     *
     * @param string $tableName The table name.
     * @param array $values The values.
     * @return array The table rules.
     */
    public function getTableRules(string $tableName, array $values): array
    {
        $tableRules = [];
        if (!empty($values['columns'])) {
            foreach ($values['columns'] as $columnName => $columnOperatorValue) {
                if (!str_contains($columnName, '_at')) {
                    $tableRules['columns.' . $columnName] = ['nullable', 'array'];
                    $tableRules['columns.' . $columnName . '.operator'] = ['required', 'string', 'in:' . implode(',', array_merge(Controller::FILTER_COMPARATOR, Controller::FILTER_COMPARATOR_WITH_ARRAY, Controller::FILTER_COMPARATOR_WITHOUT_VALUE))];
                    $tableRules['columns.' . $columnName . '.value'] = ['nullable', Rule::requiredIf(!empty($values['columns'][$columnName]['operator']) && !in_array($values['columns'][$columnName]['operator'], Controller::FILTER_COMPARATOR_WITHOUT_VALUE))];

                    if (!empty($values['columns'][$columnName]['operator']) && in_array($values['columns'][$columnName]['operator'], Controller::FILTER_COMPARATOR_WITH_ARRAY)) {
                        $tableRules['columns.' . $columnName . '.value'] = ['required', 'array'];
                    }
                }
            }
        }

        return $tableRules;
    }
}
