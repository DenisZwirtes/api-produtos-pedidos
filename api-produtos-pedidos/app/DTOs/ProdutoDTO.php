<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ProdutoDTO
{
    public function __construct(
        public readonly string $nome,
        public readonly float $preco,
        public readonly int $estoque,
        public readonly string $categoria,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'preco' => 'required|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'categoria' => 'required|string|max:255',
        ]);

        return new self(
            nome: $validated['nome'],
            preco: (float) $validated['preco'],
            estoque: (int) $validated['estoque'],
            categoria: $validated['categoria'],
        );
    }

    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'preco' => $this->preco,
            'estoque' => $this->estoque,
            'categoria' => $this->categoria,
        ];
    }
}
