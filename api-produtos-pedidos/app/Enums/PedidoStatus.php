<?php

namespace App\Enums;

enum PedidoStatus: string
{
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case PROCESSING = 'processing';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::CANCELLED => 'Cancelado',
            self::COMPLETED => 'Concluído',
            self::PROCESSING => 'Processando',
        };
    }

    public function canBeEdited(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING]);
    }

    public static function getEditableStatuses(): array
    {
        return [self::PENDING];
    }
}
