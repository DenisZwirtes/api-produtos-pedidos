<?php

namespace App\Builders;

use App\Models\Pedido;
use App\Models\Produto;
use App\DTOs\PedidoDTO;
use Illuminate\Support\Facades\DB;
use App\Exceptions\PedidoException;

class PedidoBuilder
{
    private Pedido $pedido;
    private array $items = [];
    private bool $isUpdate = false;
    private ?Pedido $existingPedido = null;

    public function __construct()
    {
        $this->pedido = new Pedido();
    }

    public static function create(): self
    {
        return new self();
    }

    public static function update(Pedido $pedido): self
    {
        $builder = new self();
        $builder->isUpdate = true;
        $builder->existingPedido = $pedido;
        $builder->pedido = $pedido;

        return $builder;
    }

    public function withUser(int $userId): self
    {
        $this->pedido->user_id = $userId;
        return $this;
    }

    public function withStatus(string $status): self
    {
        $this->pedido->status = $status;
        return $this;
    }

    public function withItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function fromDTO(PedidoDTO $dto): self
    {
        $this->withUser($dto->user_id)
             ->withStatus($dto->status)
             ->withItems($dto->items);

        return $this;
    }

    public function build(): Pedido
    {
        return DB::transaction(function () {
            if ($this->isUpdate) {
                $this->restoreStock();
                $this->clearExistingItems();
            }

            if (!$this->isUpdate) {
                $this->pedido->save();
            }

            $this->processItems();

            return $this->pedido->fresh();
        });
    }

    private function restoreStock(): void
    {
        DB::statement("
            UPDATE produtos
            INNER JOIN pedido_produtos ON produtos.id = pedido_produtos.produto_id
            SET produtos.estoque = produtos.estoque + pedido_produtos.quantidade
            WHERE pedido_produtos.pedido_id = ?
        ", [$this->existingPedido->id]);
    }

    private function clearExistingItems(): void
    {
        DB::table('pedido_produtos')
            ->where('pedido_id', $this->existingPedido->id)
            ->delete();
    }

    private function processItems(): void
    {
        foreach ($this->items as $item) {
            $produto = Produto::lockForUpdate()->find($item->produto_id);

            if ($produto->estoque < $item->quantidade) {
                throw PedidoException::insufficientStock(
                    $produto->id,
                    $produto->estoque,
                    $item->quantidade
                );
            }

            $produto->decrement('estoque', $item->quantidade);

            DB::table('pedido_produtos')->insert([
                'pedido_id' => $this->pedido->id,
                'produto_id' => $produto->id,
                'quantidade' => $item->quantidade,
                'preco_unitario' => $produto->preco,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
