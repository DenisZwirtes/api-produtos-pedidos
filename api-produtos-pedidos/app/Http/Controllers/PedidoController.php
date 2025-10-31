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

	public function show(Request $request, Pedido $pedido)
	{
		$pedido = $this->findAuthorized($request, $pedido->id);
		$pedido->load('items.produto');

		return new PedidoResource($pedido);
	}

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
