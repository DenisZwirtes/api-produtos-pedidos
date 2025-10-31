<?php

namespace App\Exceptions;

use App\Enums\HttpStatus;
use App\Enums\ValidationMessages;

class AuthException extends BaseApiException
{
    public static function invalidCredentials(): self
    {
        return new self(
            message: ValidationMessages::CREDENCIAIS_INVALIDAS->value,
            httpStatus: HttpStatus::UNAUTHORIZED
        );
    }
}
