<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PedidoStatus;

class Pedido extends Model
{
	use HasFactory;

	protected $table = 'pedidos';

	protected $fillable = [
		'user_id',
		'status',
	];

	protected $casts = [
		'status' => PedidoStatus::class,
	];

	public function items()
	{
		return $this->hasMany(PedidoProduto::class, 'pedido_id');
	}
}
