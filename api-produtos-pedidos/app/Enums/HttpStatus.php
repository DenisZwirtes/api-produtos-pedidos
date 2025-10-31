<?php

namespace App\Enums;

enum HttpStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case CONFLICT = 409;
    case UNPROCESSABLE_ENTITY = 422;
    case INTERNAL_SERVER_ERROR = 500;

    public function message(): string
    {
        return match($this) {
            self::OK => 'Sucesso',
            self::CREATED => 'Criado com sucesso',
            self::NO_CONTENT => 'Sem conteúdo',
            self::BAD_REQUEST => 'Requisição inválida',
            self::UNAUTHORIZED => 'Não autorizado',
            self::FORBIDDEN => 'Acesso negado',
            self::NOT_FOUND => 'Não encontrado',
            self::CONFLICT => 'Conflito',
            self::UNPROCESSABLE_ENTITY => 'Entidade não processável',
            self::INTERNAL_SERVER_ERROR => 'Erro interno do servidor',
        };
    }
}
