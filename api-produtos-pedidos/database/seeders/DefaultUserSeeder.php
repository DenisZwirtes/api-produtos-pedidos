<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
	public function run(): void
	{
		User::firstOrCreate(
			['email' => 'tester@example.com'],
			[
				'name' => 'Tester',
				'password' => Hash::make('password123'),
			]
		);
	}
}
