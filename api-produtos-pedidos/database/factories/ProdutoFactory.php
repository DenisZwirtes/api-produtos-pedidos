<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoFactory extends Factory
{
    public function definition(): array
    {
        return [
			'nome' => $this->faker->words(3, true),
			'preco' => $this->faker->randomFloat(2, 1, 1000),
			'estoque' => $this->faker->numberBetween(0, 100),
			'categoria' => $this->faker->randomElement(['eletronicos', 'livros', 'roupas']),
        ];
    }
}
