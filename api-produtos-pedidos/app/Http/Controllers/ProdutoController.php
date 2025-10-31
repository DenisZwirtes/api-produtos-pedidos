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
	public function index(Request $request)
	{
		$perPage = (int) $request->get('per_page', 15);
		$page = (int) $request->get('page', 1);
		$cacheKey = CacheKeys::PRODUTOS_LIST->format($page, $perPage);

        return Cache::remember($cacheKey, CacheKeys::PRODUTOS_LIST->ttl(), function () use ($perPage) {
			return ProdutoResource::collection(Produto::query()->paginate($perPage));
		});
	}

	public function show(Produto $produto)
	{
		return new ProdutoResource($produto);
	}

	public function store(Request $request)
	{
		$dto = ProdutoDTO::fromRequest($request);
		$produto = ProdutoBuilder::create()
			->fromDTO($dto)
			->build();

		return (new ProdutoResource($produto))
			->response()
			->setStatusCode(201);
	}

	public function update(Request $request, Produto $produto)
	{
		$dto = ProdutoDTO::fromRequest($request);
		$produto = ProdutoBuilder::update($produto)
			->fromDTO($dto)
			->build();

		return new ProdutoResource($produto);
	}

	public function destroy(Produto $produto)
	{
		$builder = ProdutoBuilder::update($produto);

		if (!$builder->canDelete()) {
			throw ProdutoException::productInOrder($produto->id);
		}

		$builder->delete();
		return response()->json([], HttpStatus::NO_CONTENT->value);
	}
}
