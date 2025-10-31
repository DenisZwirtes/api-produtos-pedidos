<?php

namespace App\Exceptions;

use App\Enums\HttpStatus;
use App\Enums\ValidationMessages;
use App\Enums\PedidoStatus;

class PedidoException extends BaseApiException
{
    public static function insufficientStock(int $produtoId, int $quantidadeDisponivel, int $quantidadeSolicitada): self
    {
        return new self(
            message: ValidationMessages::ESTOQUE_INSUFICIENTE->format($produtoId),
            httpStatus: HttpStatus::UNPROCESSABLE_ENTITY,
            errors: [
                'produto_id' => $produtoId,
                'quantidade_disponivel' => $quantidadeDisponivel,
                'quantidade_solicitada' => $quantidadeSolicitada,
            ]
        );
    }

    public static function notEditable(PedidoStatus $status): self
    {
        return new self(
            message: ValidationMessages::PEDIDO_NAO_EDITAVEL->value,
            httpStatus: HttpStatus::UNPROCESSABLE_ENTITY,
            errors: ['status_atual' => $status->value]
        );
    }

    public static function accessDenied(int $pedidoId, int $userId): self
    {
        return new self(
            message: 'Acesso negado ao pedido',
            httpStatus: HttpStatus::FORBIDDEN,
            errors: [
                'pedido_id' => $pedidoId,
                'user_id' => $userId,
            ]
        );
    }
}
