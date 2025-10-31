<?php

namespace App\Exceptions;

use App\Enums\HttpStatus;
use App\Enums\ValidationMessages;

class ProdutoException extends BaseApiException
{
    public static function productInOrder(int $produtoId): self
    {
        return new self(
            message: ValidationMessages::PRODUTO_EM_PEDIDO->value,
            httpStatus: HttpStatus::CONFLICT,
            errors: ['produto_id' => $produtoId]
        );
    }
}
