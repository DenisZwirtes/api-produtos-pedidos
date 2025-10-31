<?php

namespace App\Builders;

use App\Models\User;
use App\DTOs\AuthDTO;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\AuthException;

class AuthBuilder
{
    private User $user;
    private array $data = [];

    public function __construct()
    {
        $this->user = new User();
    }

    public static function create(): self
    {
        return new self();
    }

    public function fromDTO(AuthDTO $dto): self
    {
        $this->withData($dto->toArray());
        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->data['name'] = $name;
        return $this;
    }

    public function withEmail(string $email): self
    {
        $this->data['email'] = $email;
        return $this;
    }

    public function withPassword(string $password): self
    {
        $this->data['password'] = Hash::make($password);
        return $this;
    }

    public function build(): User
    {
        if (empty($this->data)) {
            throw new \InvalidArgumentException('Dados do usuário não fornecidos');
        }

        $this->user->fill($this->data);
        $this->user->save();

        return $this->user;
    }

    public function createWithToken(): array
    {
        $user = $this->build();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function authenticate(): ?User
    {
        if (empty($this->data['email']) || empty($this->data['password'])) {
            return null;
        }

        $user = User::where('email', $this->data['email'])->first();

        if (!$user || !Hash::check($this->data['password'], $user->password)) {
            return null;
        }

        return $user;
    }

    public function loginWithToken(): array
    {
        $user = $this->authenticate();
        
        if (!$user) {
            throw AuthException::invalidCredentials();
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
