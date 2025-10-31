<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use App\Http\Resources\PedidoResource;
use App\DTOs\PedidoDTO;
use App\Builders\PedidoBuilder;
use App\Enums\PedidoStatus;
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

        return PedidoResource::collection(
			Pedido::with('items.produto')
				->where('user_id', $request->user()->id)
				->orderByDesc('id')
				->paginate($perPage)
		);
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
}
