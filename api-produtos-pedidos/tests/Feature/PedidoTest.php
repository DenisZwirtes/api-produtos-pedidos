<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class PedidoTest extends TestCase
{
	use RefreshDatabase;

    private function act(User $user): void
    {
        Sanctum::actingAs($user);
    }

	public function test_criar_pedido_decrementa_estoque(): void
	{
		$user = User::factory()->create();
		$produto = Produto::factory()->create(['estoque' => 5, 'preco' => 10]);

        $this->act($user);
        $response = $this->post('/api/pedidos', [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 2]
			]
		]);

		$response->assertCreated();
		$this->assertDatabaseHas('produtos', ['id' => $produto->id, 'estoque' => 3]);
	}

	public function test_criar_pedido_com_estoque_insuficiente_falha(): void
	{
		$user = User::factory()->create();
		$produto = Produto::factory()->create(['estoque' => 1]);

        $this->act($user);
        $this->post('/api/pedidos', [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 5]
			]
		])->assertStatus(422);
	}

    public function test_apenas_dono_visualiza_lista_own_only(): void
	{
		$dono = User::factory()->create();
		$outro = User::factory()->create();
		$produto = Produto::factory()->create(['estoque' => 2]);

        $this->act($dono);
        $pedidoId = $this->post('/api/pedidos', [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 1]
			]
        ])->json('data.id');

        $this->act($outro);
        $response = $this->get('/api/pedidos');
        $response->assertOk();
        $this->assertNotContains($pedidoId, array_column($response->json('data'), 'id'));
	}

	public function test_cancelar_pedido_altera_status(): void
	{
		$user = User::factory()->create();
		$produto = Produto::factory()->create(['estoque' => 2]);

		$this->act($user);
		$response = $this->post('/api/pedidos', [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 1]
			]
		]);
		$pedidoId = $response->json('data.id');

		$this->act($user);
		$this->get('/api/pedidos/'.$pedidoId)->assertOk();
		
		$this->act($user);
		$this->get('/api/pedidos/'.$pedidoId.'/cancel')
			->assertOk()
			->assertJson(['data' => ['status' => 'cancelled']]);
	}

	public function test_atualizar_pedido(): void
	{
		$user = User::factory()->create();
		$produto = Produto::factory()->create(['estoque' => 10]);

		$this->act($user);
		$response = $this->post('/api/pedidos', [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 2]
			]
		]);
		$pedidoId = $response->json('data.id');

		$this->act($user);
		$this->put('/api/pedidos/'.$pedidoId, [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 3]
			]
		])
			->assertOk()
			->assertJsonStructure(['data' => ['id', 'status', 'items']]);
	}
}
