<?php

namespace App\Enums;

enum CacheKeys: string
{
    case PRODUTOS_LIST = 'produtos:page:%d:per:%d';
    case PRODUTO_DETAIL = 'produto:%d';
    case PEDIDOS_USER = 'pedidos:user:%d:page:%d:per:%d';

    public function format(int ...$args): string
    {
        return sprintf($this->value, ...$args);
    }

    public function ttl(): int
    {
        return match($this) {
            self::PRODUTOS_LIST => 60, // 1 minuto
            self::PRODUTO_DETAIL => 300, // 5 minutos
            self::PEDIDOS_USER => 30, // 30 segundos
        };
    }
}
