<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class PedidoDTO
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $status,
        public readonly array $items, // array of PedidoItemDTO
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.produto_id' => 'required|exists:produtos,id',
            'items.*.quantidade' => 'required|integer|min:1',
        ]);

        $items = collect($validated['items'])
            ->map(fn($item) => PedidoItemDTO::fromArray($item))
            ->toArray();

        return new self(
            user_id: $request->user()->id,
            status: \App\Enums\PedidoStatus::PENDING->value,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'status' => $this->status,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }
}
