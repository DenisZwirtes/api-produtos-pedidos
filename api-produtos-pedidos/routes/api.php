<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\PedidoController;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Pedidos (autenticadas) - cancel deve vir ANTES das rotas com {pedido}
    Route::get('pedidos/{id}/cancel', [PedidoController::class, 'cancel'])->whereNumber('id');
    Route::get('pedidos', [PedidoController::class, 'index']);
    Route::post('pedidos', [PedidoController::class, 'store']);
    Route::get('pedidos/{pedido}', [PedidoController::class, 'show']);
    Route::put('pedidos/{pedido}', [PedidoController::class, 'update']);
});

// Produtos (públicas)
Route::apiResource('produtos', ProdutoController::class)->only(['index', 'show', 'store', 'update', 'destroy']);


