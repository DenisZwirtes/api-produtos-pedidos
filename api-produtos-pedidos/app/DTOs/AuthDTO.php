<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class AuthDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromRegisterRequest(Request $request): self
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );
    }

    public static function fromLoginRequest(Request $request): self
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        return new self(
            name: '', // não usado no login
            email: $validated['email'],
            password: $validated['password'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
