<?php

namespace App\Builders;

use App\Models\Produto;
use App\DTOs\ProdutoDTO;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProdutoBuilder
{
    private Produto $produto;
    private array $data = [];

    public function __construct()
    {
        $this->produto = new Produto();
    }

    public static function create(): self
    {
        return new self();
    }

    public static function update(Produto $produto): self
    {
        $builder = new self();
        $builder->produto = $produto;
        return $builder;
    }

    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function fromDTO(ProdutoDTO $dto): self
    {
        $this->withData($dto->toArray());
        return $this;
    }


    public function build(): Produto
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException('Dados do produto não fornecidos');
        }

        $this->produto->fill($this->data);
        $this->produto->save();

        return $this->produto;
    }

    public function canDelete(): bool
    {
        return !DB::table('pedido_produtos')
            ->where('produto_id', $this->produto->id)
            ->exists();
    }

    public function delete(): bool
    {
        if (!$this->canDelete()) {
            return false;
        }

        return $this->produto->delete();
    }
}
