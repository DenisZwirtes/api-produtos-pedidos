<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'user_id' => (int) $this->user_id,
			'status' => $this->status,
			'items' => $this->items->map(function ($item) {
				return [
					'produto_id' => (int) $item->produto_id,
					'nome' => $item->produto->nome,
					'categoria' => $item->produto->categoria,
					'quantidade' => (int) $item->quantidade,
					'preco_unitario' => (float) $item->preco_unitario,
				];
			}),
			'created_at' => optional($this->created_at)->toISOString(),
			'updated_at' => optional($this->updated_at)->toISOString(),
		];
	}
}
