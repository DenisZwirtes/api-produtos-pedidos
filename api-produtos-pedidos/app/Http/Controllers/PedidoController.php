<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use App\Http\Resources\PedidoResource;
use Illuminate\Support\Facades\Cache;
use App\DTOs\PedidoDTO;
use App\Builders\PedidoBuilder;
use App\Enums\PedidoStatus;
use App\Enums\CacheKeys;
use App\Exceptions\PedidoException;

class PedidoController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/pedidos",
     *   tags={"Pedidos"},
     *   summary="Lista pedidos do usuário autenticado",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
	public function index(Request $request)
	{
		$perPage = (int) $request->get('per_page', 15);
		$page = (int) $request->get('page', 1);
		$userId = $request->user()->id;

		$this->registerPerPage($perPage);

		$cacheKey = CacheKeys::PEDIDOS_USER->format($userId, $page, $perPage);

        return Cache::remember($cacheKey, CacheKeys::PEDIDOS_USER->ttl(), function () use ($userId, $perPage) {
			return PedidoResource::collection(
				Pedido::with('items.produto')
					->where('user_id', $userId)
					->orderByDesc('id')
					->paginate($perPage)
			);
		});
	}

    /**
     * @OA\Get(
     *   path="/api/pedidos/{id}",
     *   tags={"Pedidos"},
     *   summary="Exibe um pedido do usuário",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=403, description="Acesso negado")
     * )
     */
	public function show(Request $request, Pedido $pedido)
	{
		$pedido = $this->findAuthorized($request, $pedido->id);
		$pedido->load('items.produto');

		return new PedidoResource($pedido);
	}

    /**
     * @OA\Post(
     *   path="/api/pedidos",
     *   tags={"Pedidos"},
     *   summary="Cria pedido (autenticado)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(required={"items"},
     *       @OA\Property(property="items", type="array",
     *         @OA\Items(type="object",
     *           @OA\Property(property="produto_id", type="integer"),
     *           @OA\Property(property="quantidade", type="integer")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Criado")
     * )
     */
	public function store(Request $request)
	{
		$dto = PedidoDTO::fromRequest($request);

		$pedido = PedidoBuilder::create()
			->fromDTO($dto)
			->build();

		$this->invalidatePedidosCache($request->user()->id);

		return (new PedidoResource($pedido))
			->response()
			->setStatusCode(201);
	}

    /**
     * @OA\Put(
     *   path="/api/pedidos/{id}",
     *   tags={"Pedidos"},
     *   summary="Atualiza pedido (pendente)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(required={"items"},
     *       @OA\Property(property="items", type="array",
     *         @OA\Items(type="object",
     *           @OA\Property(property="produto_id", type="integer"),
     *           @OA\Property(property="quantidade", type="integer")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
	public function update(Request $request, Pedido $pedido)
	{
		$pedido = $this->findAuthorized($request, $pedido->id);

        if (!$pedido->status->canBeEdited()) {
			throw PedidoException::notEditable($pedido->status);
		}

		$dto = PedidoDTO::fromRequest($request);

		$pedido = PedidoBuilder::update($pedido)
			->fromDTO($dto)
			->build();

		$this->invalidatePedidosCache($request->user()->id);

		return new PedidoResource($pedido);
	}

    /**
     * @OA\Put(
     *   path="/api/pedidos/{id}/cancel",
     *   tags={"Pedidos"},
     *   summary="Cancela pedido",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
	public function cancel(Request $request, $id)
	{
		$pedido = $this->findAuthorized($request, $id);

        if ($pedido->status === PedidoStatus::CANCELLED) {
			return new PedidoResource($pedido);
		}

		$pedido->update(['status' => PedidoStatus::CANCELLED]);

		$this->invalidatePedidosCache($request->user()->id);

		return new PedidoResource($pedido);
	}

	private function findAuthorized(Request $request, $pedidoId): Pedido
	{
		$pedido = Pedido::where('id', $pedidoId)
			->where('user_id', $request->user()->id)
			->first();

        if (!$pedido) {
			throw PedidoException::accessDenied($pedidoId, $request->user()->id);
		}

		return $pedido;
	}

	/**
	 * Registra um valor de per_page usado em uma requisição no cache Redis
	 * Isso permite invalidar apenas os valores realmente utilizados
	 */
	private function registerPerPage(int $perPage): void
	{
		$cacheKey = 'pedidos:used_per_pages';
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
	 * Invalida todas as chaves de cache relacionadas à listagem de pedidos do usuário
	 * Usa os valores de per_page que foram realmente utilizados nas requisições (armazenados no cache)
	 * Se nenhum valor foi registrado, usa valores padrão comuns
	 */
	private function invalidatePedidosCache(int $userId): void
	{
		$cacheKey = 'pedidos:used_per_pages';
		$usedPerPages = Cache::get($cacheKey, []);

		$defaultPerPages = [15, 30, 50, 100];

		$perPages = array_unique(array_merge($usedPerPages, $defaultPerPages));

		foreach ($perPages as $perPage) {
			for ($page = 1; $page <= 10; $page++) {
				Cache::forget(CacheKeys::PEDIDOS_USER->format($userId, $page, $perPage));
			}
		}
	}
}
