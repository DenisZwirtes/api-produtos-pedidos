<?php

namespace App\Exceptions;

use App\Enums\HttpStatus;
use Illuminate\Http\JsonResponse;
use Throwable;

abstract class BaseApiException extends \Exception
{
    protected HttpStatus $httpStatus;
    protected array $errors = [];

    public function __construct(
        string $message = '',
        HttpStatus $httpStatus = HttpStatus::INTERNAL_SERVER_ERROR,
        array $errors = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpStatus = $httpStatus;
        $this->errors = $errors;
    }


    public function render(): JsonResponse
    {
        $response = [
            'message' => $this->getMessage(),
            'status' => $this->httpStatus->value,
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTraceAsString(),
            ];
        }

        return response()->json($response, $this->httpStatus->value);
    }
}
