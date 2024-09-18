<?php

namespace IsoDoss\TerritorialDivision\Http\Resources;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class SendApiResponse extends JsonResource
{
    /**
     * The success.
     *
     * @var bool The success.
     */
    public bool $success = true;

    /**
     * The message.
     *
     * @var string The message.
     */
    public string $message = '';

    public array|MessageBag $errors = [];

    public array $warnings = [];

    public array $input = [];

    public array|LengthAwarePaginator|Collection $data = [];

    public int $statusCode = 200;

    /**
     * Create a new resource instance.
     *
     * @param bool $success The success.
     * @param string $message The message
     * @param array|MessageBag $errors The errors.
     * @param array $warnings The warnings.
     * @param array $input The input.
     * @param array|LengthAwarePaginator|Collection $data The data.
     * @param int $statusCode The status code.
     */
    public function __construct(bool $success = true, string $message = '', array|MessageBag $errors = [], array $warnings = [], array $input = [], array|LengthAwarePaginator|Collection $data = [], int $statusCode = 200)
    {
        parent::__construct($data);
        $this->success = $success;
        $this->message = $message;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->input = $input;
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'input' => $this->input,
            'output' => $this->data,
            //'data' => $this->data,
        ];
    }

    /**
     * Get response.
     *
     * @param Request|null $request The request.
     * @return JsonResponse The json response.
     */
    public function response($request = null): JsonResponse
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'input' => $this->input,
            'output' => $this->data,
            //'data' => $this->data,
        ];

        if ($this->success) {
            Log::info('Api response', $response);
        } else {
            Log::error('Api response', $response);
        }

        return response()->json($response, $this->statusCode);
    }
}
