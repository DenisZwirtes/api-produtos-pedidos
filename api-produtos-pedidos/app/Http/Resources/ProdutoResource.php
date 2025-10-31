<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'nome' => $this->nome,
			'preco' => (float) $this->preco,
			'estoque' => (int) $this->estoque,
			'categoria' => $this->categoria,
			'created_at' => optional($this->created_at)->toISOString(),
			'updated_at' => optional($this->updated_at)->toISOString(),
		];
	}
}
