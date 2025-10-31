<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use App\Http\Resources\ProdutoResource;
use Illuminate\Support\Facades\Cache;
use App\DTOs\ProdutoDTO;
use App\Builders\ProdutoBuilder;
use App\Enums\HttpStatus;
use App\Enums\CacheKeys;
use App\Exceptions\ProdutoException;

class ProdutoController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/produtos",
     *   tags={"Produtos"},
     *   summary="Lista produtos (público)",
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
	public function index(Request $request)
	{
		$perPage = (int) $request->get('per_page', 15);
		$page = (int) $request->get('page', 1);

		$this->registerPerPage($perPage);

		$cacheKey = CacheKeys::PRODUTOS_LIST->format($page, $perPage);

        return Cache::remember($cacheKey, CacheKeys::PRODUTOS_LIST->ttl(), function () use ($perPage) {
			return ProdutoResource::collection(Produto::query()->paginate($perPage));
		});
	}

    /**
     * @OA\Get(
     *   path="/api/produtos/{id}",
     *   tags={"Produtos"},
     *   summary="Exibe um produto",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Não encontrado")
     * )
     */
	public function show(Produto $produto)
	{
		return new ProdutoResource($produto);
	}

    /**
     * @OA\Post(
     *   path="/api/produtos",
     *   tags={"Produtos"},
     *   summary="Cria produto (público)",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(required={"nome","preco","estoque","categoria"},
     *       @OA\Property(property="nome", type="string"),
     *       @OA\Property(property="preco", type="number", format="float"),
     *       @OA\Property(property="estoque", type="integer"),
     *       @OA\Property(property="categoria", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Criado")
     * )
     */
	public function store(Request $request)
	{
		$dto = ProdutoDTO::fromRequest($request);
		$produto = ProdutoBuilder::create()
			->fromDTO($dto)
			->build();

		$this->invalidateProdutosCache();

		return (new ProdutoResource($produto))
			->response()
			->setStatusCode(201);
	}

    /**
     * @OA\Put(
     *   path="/api/produtos/{id}",
     *   tags={"Produtos"},
     *   summary="Atualiza produto",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(required={"nome","preco","estoque","categoria"},
     *       @OA\Property(property="nome", type="string"),
     *       @OA\Property(property="preco", type="number", format="float"),
     *       @OA\Property(property="estoque", type="integer"),
     *       @OA\Property(property="categoria", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
	public function update(Request $request, Produto $produto)
	{
		$dto = ProdutoDTO::fromRequest($request);
		$produto = ProdutoBuilder::update($produto)
			->fromDTO($dto)
			->build();

		$this->invalidateProdutosCache();
		Cache::forget(CacheKeys::PRODUTO_DETAIL->format($produto->id));

		return new ProdutoResource($produto);
	}

    /**
     * @OA\Delete(
     *   path="/api/produtos/{id}",
     *   tags={"Produtos"},
     *   summary="Remove produto (bloqueia se em pedido)",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Sem conteúdo"),
     *   @OA\Response(response=409, description="Produto em pedido")
     * )
     */
	public function destroy(Produto $produto)
	{
		$builder = ProdutoBuilder::update($produto);

		if (!$builder->canDelete()) {
			throw ProdutoException::productInOrder($produto->id);
		}

		$builder->delete();

		$this->invalidateProdutosCache();
		Cache::forget(CacheKeys::PRODUTO_DETAIL->format($produto->id));

		return response()->json([], HttpStatus::NO_CONTENT->value);
	}

	/**
	 * Registra um valor de per_page usado em uma requisição no cache Redis
	 * Isso permite invalidar apenas os valores realmente utilizados
	 */
	private function registerPerPage(int $perPage): void
	{
		$cacheKey = 'produtos:used_per_pages';
		$usedPerPages = Cache::get($cacheKey, []);

		if (!in_array($perPage, $usedPerPages, true)) {
			$usedPerPages[] = $perPage;

			if (count($usedPerPages) > 50) {
				$usedPerPages = array_slice($usedPerPages, -50);
			}

			Cache::put($cacheKey, $usedPerPages, 3600);
		}
	}

	/**
	 * Invalida todas as chaves de cache relacionadas à listagem de produtos
	 * Usa os valores de per_page que foram realmente utilizados nas requisições (armazenados no cache)
	 * Se nenhum valor foi registrado, usa valores padrão comuns
	 */
	private function invalidateProdutosCache(): void
	{
		$cacheKey = 'produtos:used_per_pages';
		$usedPerPages = Cache::get($cacheKey, []);

		$defaultPerPages = [15, 30, 50, 100];

		$perPages = array_unique(array_merge($usedPerPages, $defaultPerPages));

		foreach ($perPages as $perPage) {
			for ($page = 1; $page <= 10; $page++) {
				Cache::forget(CacheKeys::PRODUTOS_LIST->format($page, $perPage));
			}
		}
	}
}
