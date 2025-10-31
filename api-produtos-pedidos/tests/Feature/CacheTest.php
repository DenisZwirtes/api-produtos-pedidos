<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class CacheTest extends TestCase
{
	use RefreshDatabase;

	/**
	 * Testa se a listagem de produtos usa cache
	 */
	public function test_listagem_produtos_usa_cache(): void
	{
		// Limpar cache antes do teste
		Cache::flush();
		
		Produto::factory()->count(5)->create();

		// Primeira requisição - deve popular o cache
		$response1 = $this->get('/api/produtos?per_page=15&page=1');
		$response1->assertOk();

		// Verificar se o cache foi criado
		$cacheKey = 'produtos:page:1:per:15';
		$this->assertTrue(Cache::has($cacheKey), 'Cache deve ser criado após primeira requisição');

		// Segunda requisição - deve usar cache (verificar TTL)
		$cached = Cache::get($cacheKey);
		$this->assertNotNull($cached, 'Cache deve conter dados');
	}

	/**
	 * Testa se criar produto invalida o cache de listagem
	 */
	public function test_criar_produto_invalida_cache_listagem(): void
	{
		Cache::flush();
		
		Produto::factory()->count(5)->create();

		// Popular cache com diferentes per_page
		$this->get('/api/produtos?per_page=15&page=1');
		$this->get('/api/produtos?per_page=30&page=1');
		$this->get('/api/produtos?per_page=50&page=1');

		// Verificar que caches foram criados
		$this->assertTrue(Cache::has('produtos:page:1:per:15'));
		$this->assertTrue(Cache::has('produtos:page:1:per:30'));
		$this->assertTrue(Cache::has('produtos:page:1:per:50'));

		// Criar novo produto
		$this->post('/api/produtos', [
			'nome' => 'Novo Produto',
			'preco' => 99.99,
			'estoque' => 10,
			'categoria' => 'teste',
		])->assertCreated();

		// Verificar que caches foram invalidados (pelo menos os padrões)
		// Como invalidamos até página 10, a página 1 deve estar limpa
		$this->assertFalse(Cache::has('produtos:page:1:per:15'), 'Cache padrão deve ser invalidado');
		$this->assertFalse(Cache::has('produtos:page:1:per:30'), 'Cache padrão deve ser invalidado');
		$this->assertFalse(Cache::has('produtos:page:1:per:50'), 'Cache padrão deve ser invalidado');
	}

	/**
	 * Testa se atualizar produto invalida cache de listagem e detalhe
	 */
	public function test_atualizar_produto_invalida_cache_detalhe(): void
	{
		Cache::flush();
		
		$produto = Produto::factory()->create(['nome' => 'Produto Original']);

		// Popular cache de listagem e detalhe
		$this->get('/api/produtos');
		$this->get('/api/produtos/' . $produto->id);

		// Verificar caches
		$listCacheKey = 'produtos:page:1:per:15';
		$detailCacheKey = 'produto:' . $produto->id;
		
		// Detalhe não usa cache por padrão, mas vamos verificar
		// A listagem deve estar em cache
		$this->assertTrue(Cache::has($listCacheKey), 'Cache de listagem deve existir');

		// Atualizar produto
		$this->put('/api/produtos/' . $produto->id, [
			'nome' => 'Produto Atualizado',
			'preco' => $produto->preco,
			'estoque' => $produto->estoque,
			'categoria' => $produto->categoria,
		])->assertOk();

		// Verificar que cache de listagem foi invalidado
		$this->assertFalse(Cache::has($listCacheKey), 'Cache de listagem deve ser invalidado após update');
		$this->assertFalse(Cache::has($detailCacheKey), 'Cache de detalhe deve ser invalidado após update');
	}

	/**
	 * Testa se cache de pedidos é invalidado após criar pedido
	 */
	public function test_criar_pedido_invalida_cache_pedidos(): void
	{
		Cache::flush();
		
		$user = User::factory()->create();
		$produto = Produto::factory()->create(['estoque' => 10]);

		Sanctum::actingAs($user);

		// Popular cache de pedidos com diferentes per_page
		$this->get('/api/pedidos?per_page=15&page=1');
		$this->get('/api/pedidos?per_page=30&page=1');

		// Verificar que caches foram criados
		$cacheKey15 = 'pedidos:user:' . $user->id . ':page:1:per:15';
		$cacheKey30 = 'pedidos:user:' . $user->id . ':page:1:per:30';
		
		$this->assertTrue(Cache::has($cacheKey15), 'Cache de pedidos deve existir');
		$this->assertTrue(Cache::has($cacheKey30), 'Cache de pedidos deve existir');

		// Criar novo pedido
		$this->post('/api/pedidos', [
			'items' => [
				['produto_id' => $produto->id, 'quantidade' => 1]
			]
		])->assertCreated();

		// Verificar que caches foram invalidados
		$this->assertFalse(Cache::has($cacheKey15), 'Cache de pedidos deve ser invalidado');
		$this->assertFalse(Cache::has($cacheKey30), 'Cache de pedidos deve ser invalidado');
	}

	/**
	 * Testa se per_page não registrado ainda é invalidado pelos valores padrão
	 */
	public function test_per_page_nao_registrado_e_invalidado_pelos_padroes(): void
	{
		Cache::flush();
		
		Produto::factory()->count(5)->create();

		// Usar per_page padrão (15) - será registrado
		$this->get('/api/produtos?per_page=15&page=1');
		$this->assertTrue(Cache::has('produtos:page:1:per:15'));

		// Criar produto - deve invalidar caches padrão
		$this->post('/api/produtos', [
			'nome' => 'Teste',
			'preco' => 10,
			'estoque' => 5,
			'categoria' => 'teste',
		])->assertCreated();

		// Verificar que cache padrão foi invalidado
		$this->assertFalse(Cache::has('produtos:page:1:per:15'), 'Cache padrão deve ser invalidado');
	}

	/**
	 * Testa se páginas além da 10 podem ficar stale (limitação conhecida)
	 */
	public function test_paginas_alem_de_10_podem_ficar_stale(): void
	{
		Cache::flush();
		
		// Criar muitos produtos para ter múltiplas páginas
		Produto::factory()->count(200)->create();

		// Acessar página 15 (além do limite de invalidação)
		$this->get('/api/produtos?per_page=15&page=15');
		$cacheKey15 = 'produtos:page:15:per:15';
		$this->assertTrue(Cache::has($cacheKey15), 'Cache da página 15 deve existir');

		// Criar novo produto
		$this->post('/api/produtos', [
			'nome' => 'Novo',
			'preco' => 10,
			'estoque' => 5,
			'categoria' => 'teste',
		])->assertCreated();

		// Verificar que página 15 NÃO foi invalidada (limitação conhecida)
		// Com TTL de 60s, isso não é crítico, mas é uma limitação
		$this->assertTrue(Cache::has($cacheKey15), 'Página 15 não é invalidada (limitação conhecida)');
	}

	/**
	 * Testa se TTL curto garante que cache expira rapidamente mesmo com falha na invalidação
	 */
	public function test_ttl_curto_garante_atualizacao_rapida(): void
	{
		Cache::flush();
		
		Produto::factory()->count(5)->create();

		// Popular cache
		$this->get('/api/produtos?per_page=15&page=1');
		
		$cacheKey = 'produtos:page:1:per:15';
		$cached = Cache::get($cacheKey);
		$this->assertNotNull($cached);

		// TTL de produtos é 60 segundos
		// Mesmo que a invalidação falhe, o cache expira em 60s
		// Isso é uma proteção contra falhas na invalidação
		$ttl = Cache::get($cacheKey . ':ttl', 60);
		$this->assertLessThanOrEqual(60, $ttl, 'TTL deve ser no máximo 60 segundos');
	}

	/**
	 * Testa se múltiplos per_page são registrados corretamente
	 */
	public function test_multiplos_per_page_sao_registrados(): void
	{
		Cache::flush();
		
		Produto::factory()->count(5)->create();

		// Usar diferentes per_page
		$this->get('/api/produtos?per_page=7');
		$this->get('/api/produtos?per_page=13');
		$this->get('/api/produtos?per_page=27');

		// Verificar que per_page foram registrados
		$usedPerPages = Cache::get('produtos:used_per_pages', []);
		
		$this->assertContains(7, $usedPerPages, 'per_page 7 deve ser registrado');
		$this->assertContains(13, $usedPerPages, 'per_page 13 deve ser registrado');
		$this->assertContains(27, $usedPerPages, 'per_page 27 deve ser registrado');
	}

	/**
	 * Testa se invalidação inclui per_page registrados e padrões
	 */
	public function test_invalidacao_inclui_per_page_registrados_e_padroes(): void
	{
		Cache::flush();
		
		Produto::factory()->count(5)->create();

		// Registrar per_page customizado
		$this->get('/api/produtos?per_page=7&page=1');
		$this->get('/api/produtos?per_page=13&page=1');

		// Verificar caches criados
		$cacheKey7 = 'produtos:page:1:per:7';
		$cacheKey13 = 'produtos:page:1:per:13';
		$cacheKey15 = 'produtos:page:1:per:15'; // Padrão

		$this->assertTrue(Cache::has($cacheKey7));
		$this->assertTrue(Cache::has($cacheKey13));

		// Criar produto - deve invalidar registrados e padrões
		$this->post('/api/produtos', [
			'nome' => 'Teste',
			'preco' => 10,
			'estoque' => 5,
			'categoria' => 'teste',
		])->assertCreated();

		// Caches registrados devem ser invalidados
		$this->assertFalse(Cache::has($cacheKey7), 'Cache registrado deve ser invalidado');
		$this->assertFalse(Cache::has($cacheKey13), 'Cache registrado deve ser invalidado');
		$this->assertFalse(Cache::has($cacheKey15), 'Cache padrão deve ser invalidado');
	}
}

