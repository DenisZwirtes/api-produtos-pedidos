<?php

namespace App\Enums;

enum ValidationMessages: string
{
    case PRODUTO_EM_PEDIDO = 'Produto não pode ser excluído pois está presente em pedidos.';
    case PEDIDO_NAO_EDITAVEL = 'Apenas pedidos pendentes podem ser editados.';
    case CREDENCIAIS_INVALIDAS = 'Credenciais inválidas';
    case ESTOQUE_INSUFICIENTE = 'Estoque insuficiente para o produto ID %d';
    case LOGOUT_SUCESSO = 'Logout realizado com sucesso';
    case PEDIDO_JA_CANCELADO = 'Pedido já está cancelado';

    public function format(int ...$args): string
    {
        return sprintf($this->value, ...$args);
    }
}
