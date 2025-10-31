<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class PedidoItemDTO
{
    public function __construct(
        public readonly int $produto_id,
        public readonly int $quantidade,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            produto_id: (int) $data['produto_id'],
            quantidade: (int) $data['quantidade'],
        );
    }

    public function toArray(): array
    {
        return [
            'produto_id' => $this->produto_id,
            'quantidade' => $this->quantidade,
        ];
    }
}
