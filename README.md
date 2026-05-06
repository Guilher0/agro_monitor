# AgroMonitor

Sistema de gestao agro com Laravel + Inertia + Vue, preparado para multi-tenancy com Stancl Tenancy e ambiente Docker.

## Requisitos

- Docker
- Docker Compose
- Git

## Setup Inicial (do zero)

1. Clone o repositorio.

```bash
git clone https://github.com/Guilher0/agro_monitor.git
cd agro_monitor
```

2. Suba os containers.

```bash
docker compose up -d
```

3. Rode as migrations do banco central (landlord).

```bash
docker compose exec app php artisan migrate --force
```

4. Rode as migrations de tenants.

```bash
docker compose exec app php artisan tenants:migrate --force
```

5. Popule dados de exemplo (admin + tenant demo + dados agricolas).

```bash
docker compose exec app php artisan db:seed --force
```

6. Limpe cache do Laravel (recomendado no primeiro boot).

```bash
docker compose exec app php artisan optimize:clear
```

## Acesso

- App central: http://localhost:8090
- Vite (dev server): http://localhost:5174

Credenciais de demo:

- Admin central:
	- Email: admin@agromonitor.app
	- Senha: password
- Owner tenant demo:
	- Email: owner@fazenda-demo.test
	- Senha: password

## Comandos Uteis

Subir stack completa:

```bash
docker compose up -d app mysql redis vite queue
```

Ver status dos servicos:

```bash
docker compose ps
```

Logs da aplicacao:

```bash
docker compose logs -f app
```

Logs do Vite:

```bash
docker compose logs -f vite
```

Parar stack:

```bash
docker compose down
```

## Troubleshooting Rapido

Erro: service "agro_app" is not running

- Causa: `agro_app` e o nome do container, nao do servico.
- Solucao: use `app` no `docker compose exec`.

Exemplo correto:

```bash
docker compose exec app php artisan migrate --force
```

Erro: Command "tenancy:migrate" is not defined

- Solucao: o comando correto neste projeto e:

```bash
docker compose exec app php artisan tenants:migrate --force
```

Erro de tabela ja existente ao rodar migrate

- Quando o banco ja tem estado antigo/inconsistente, prefira recriar ambiente local:

```bash
docker compose down -v
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

Tela branca no frontend

- Verifique se o Vite esta ativo em `http://localhost:5174`.
- Use sempre `localhost` (evite misturar com `127.0.0.1`).
- Faca hard reload no browser (Ctrl+Shift+R).
