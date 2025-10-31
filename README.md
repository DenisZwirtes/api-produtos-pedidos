# API Produtos e Pedidos (Laravel + MySQL + Redis + Vue 3)

Projeto full stack com API REST em Laravel e frontend em Vue.js, totalmente dockerizado, com autenticação via Sanctum, DTOs, Builders, Enums, Exceptions customizadas, paginação, cache (Redis) e testes automatizados.

## Sumário
- Visão geral
- Requisitos
- Instalação rápida (recomendado)
- Como clonar este projeto (após publicar no GitHub)
- Serviços e URLs
- Credenciais de teste
- Como testar no navegador
- Endpoints principais
- Testes automatizados
- Git e publicação no GitHub

## Visão geral
- Backend: `api-produtos-pedidos` (Laravel 12+, MySQL, Redis)
- Frontend: `frontend-vue` (Vue 3, Vite, Tailwind, Axios, Vue Router)
- Docker Compose orquestra DB, Redis, API e Frontend
- Script de instalação automatiza tudo para ambiente local

## Requisitos
- Docker e Docker Compose (recomendado)
  - Instale Docker: consulte a documentação oficial
  - Habilite docker-compose-plugin (ou use `docker-compose` legado)
- Alternativa sem Docker (Ubuntu/Debian): PHP 8.2+, Composer, Node.js+npm, MySQL (localhost:3307), Redis (localhost:6379)

## Instalação rápida (recomendado)
No diretório raiz do repositório:

```bash
cd api-produtos-pedidos
bash scripts/install.sh --yes
```
O script irá:
- Copiar `.env` (se necessário) e ajustar variáveis para Docker
- Subir containers: MySQL, Redis, API (Laravel) e Frontend (Vue)
- Rodar composer install, key:generate, migrations e seed
- Confirmar disponibilidade de API e do Frontend

Caso queira pular algum serviço:
- `--no-docker` (usa serviços locais)
- `--auto-install` (Ubuntu/Debian): instala e configura dependências locais automaticamente
- `--yes` para modo não interativo

Exemplos (sem Docker, Ubuntu/Debian):
```bash
cd api-produtos-pedidos
bash scripts/install.sh --no-docker --auto-install --yes
```

## Como clonar este projeto (após publicar no GitHub)
Depois que você publicar este repositório em sua conta, outros usuários poderão clonar assim:

HTTPS:
```bash
git clone https://github.com/DenisZwirtes/api-produtos-pedidos.git
cd api-produtos-pedidos
cd api-produtos-pedidos && bash scripts/install.sh --yes
```

SSH:
```bash
git clone git@github.com:DenisZwirtes/api-produtos-pedidos.git
cd api-produtos-pedidos
cd api-produtos-pedidos && bash scripts/install.sh --yes
```

## Serviços e URLs
- API (Laravel): `http://localhost:8000`
- Frontend (Vue): `http://localhost:3000`
- Swagger UI: `http://localhost:8000/api/documentation`
- MySQL (host): `127.0.0.1:3307` | db `api_produtos_pedidos` | user `laravel` | pass `secret`
- Redis (host): `127.0.0.1:6379`

**Nota:** Dentro do container `app`, use `DB_HOST=db` e `DB_PORT=3306`.

## Credenciais de teste
- Email: `tester@example.com`
- Senha: `password123`

## Como testar no navegador
1) Abra o Frontend: `http://localhost:3000`
2) Produtos: visualizar, criar, editar e excluir
3) Login: use as credenciais de teste
4) Pedidos: criar, editar e cancelar

A API também pode ser testada diretamente:
```bash
# Produtos (público)
curl http://localhost:8000/api/produtos

# Login (retorna token)
curl -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"tester@example.com","password":"password123"}'

# Listar pedidos do usuário autenticado
curl http://localhost:8000/api/pedidos -H "Authorization: Bearer SEU_TOKEN"
```

## Endpoints principais
- Auth
  - POST `/api/register`
  - POST `/api/login`
  - POST `/api/logout` (auth)
- Produtos (público)
  - GET `/api/produtos`
  - GET `/api/produtos/{id}`
  - POST `/api/produtos`
  - PUT `/api/produtos/{id}`
  - DELETE `/api/produtos/{id}` (bloqueia se estiver em algum pedido)
- Pedidos (autenticado)
  - GET `/api/pedidos`
  - GET `/api/pedidos/{id}`
  - POST `/api/pedidos`
  - PUT `/api/pedidos/{id}`
  - PUT `/api/pedidos/{id}/cancel` (cancela: `status=cancelled`)

Paginação (ex.: produtos): `?page=1&per_page=15`.

## Testes automatizados
- Executar testes (usa banco de testes isolado `api_produtos_pedidos_test`):
```bash
php artisan test
```

**Importante:** Os testes usam um banco de dados separado (`api_produtos_pedidos_test`), então não afetam seus dados de desenvolvimento. O script de instalação cria este banco automaticamente.

## Documentação (Swagger)
- UI: `http://localhost:8000/api/documentation`
- Gerar manualmente (se necessário):
```bash
php artisan l5-swagger:generate
```

## Git e publicação no GitHub
Verifique se já é um repositório Git:
```bash
git status
```
Se não for, inicialize e faça o primeiro commit:
```bash
git init
git add .
git commit -m "chore: initial commit"
```
Crie o repositório no GitHub (ex.: `DenisZwirtes/api-produtos-pedidos`) e conecte:
```bash
git remote add origin git@github.com:DenisZwirtes/api-produtos-pedidos.git
git branch -M main
git push -u origin main
```

---
- Backend: `api-produtos-pedidos`
- Frontend: `frontend-vue`
- Script de instalação: `api-produtos-pedidos/scripts/install.sh`
