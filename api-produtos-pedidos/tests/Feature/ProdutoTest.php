<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ProdutoTest extends TestCase
{
	use RefreshDatabase;

	public function test_index_publico_funciona(): void
	{
		Produto::factory()->count(3)->create();
		$response = $this->get('/api/produtos');
		$response->assertOk()->assertJsonStructure(['data']);
	}

	public function test_criar_produto_publico(): void
	{
		$response = $this->post('/api/produtos', [
			'nome' => 'Teclado',
			'preco' => 100,
			'estoque' => 10,
			'categoria' => 'eletronicos',
		]);
		$response->assertCreated();
	}

	public function test_nao_excluir_produto_em_pedido(): void
	{
		$produto = Produto::factory()->create(['estoque' => 10]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->post('/api/pedidos', [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 1]
			]
		])->assertCreated();

		$this->delete('/api/produtos/'.$produto->id)->assertStatus(409);
	}

	public function test_mostrar_produto_especifico(): void
	{
		$produto = Produto::factory()->create();

		$this->get('/api/produtos/' . $produto->id)
			->assertOk()
			->assertJsonStructure([
				'data' => [
					'id',
					'nome',
					'preco',
					'estoque',
					'categoria',
					'created_at',
					'updated_at',
				]
			]);
	}

	public function test_atualizar_produto(): void
	{
		$produto = Produto::factory()->create();

		$this->put('/api/produtos/' . $produto->id, [
			'nome' => 'Produto Atualizado',
			'preco' => 99.99,
			'estoque' => 50,
			'categoria' => 'Eletrônicos',
		])
			->assertOk()
			->assertJson(['data' => ['nome' => 'Produto Atualizado']]);
	}
}
