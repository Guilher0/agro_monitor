# ─── AgroMonitor — Makefile ────────────────────────────────────────────────────
# Atalhos para os comandos Docker mais comuns no dia a dia de desenvolvimento.
# Uso: make <comando>  (ex: make up, make migrate, make tinker)

DC = docker compose
APP = $(DC) exec app

# ─── Ciclo de vida ─────────────────────────────────────────────────────────────

## Sobe todos os containers em background e exibe os logs
up:
	$(DC) up -d
	$(DC) logs -f app

## Para e remove os containers (mantém volumes)
down:
	$(DC) down

## Rebuilda a imagem da aplicação do zero
build:
	$(DC) build --no-cache app

## Sobe os containers após rebuild
rebuild: build up

# ─── Laravel ──────────────────────────────────────────────────────────────────

## Executa as migrations do banco central (landlord)
migrate:
	$(APP) php artisan migrate

## Executa as migrations de todos os tenants
migrate-tenants:
	$(APP) php artisan tenancy:migrate

## Reseta o banco central e roda as migrations + seeds
migrate-fresh:
	$(APP) php artisan migrate:fresh --seed

## Limpa todos os caches da aplicação
cache-clear:
	$(APP) php artisan optimize:clear

## Abre o Tinker (REPL do Laravel)
tinker:
	$(APP) php artisan tinker

## Cria um novo tenant de teste via Tinker
create-tenant:
	$(APP) php artisan tinker --execute="\
		\$$t = App\Models\Tenant::create(['id' => 'fazenda-demo', 'name' => 'Fazenda Demo', 'slug' => 'fazenda-demo']); \
		\$$t->domains()->create(['domain' => 'fazenda-demo.localhost']); \
		echo 'Tenant criado: ' . \$$t->id . PHP_EOL;"

## Exibe as rotas registradas
routes:
	$(APP) php artisan route:list

# ─── Qualidade de código ───────────────────────────────────────────────────────

## Roda o Pint (formatador de código PHP)
pint:
	$(APP) ./vendor/bin/pint

## Roda os testes com Pest
test:
	$(APP) php artisan test

# ─── Assets ───────────────────────────────────────────────────────────────────

## Compila os assets para produção
build-assets:
	$(DC) exec vite npm run build

## Exibe os logs do container da aplicação
logs:
	$(DC) logs -f app

## Abre um shell bash no container da aplicação
shell:
	$(APP) bash

.PHONY: up down build rebuild migrate migrate-tenants migrate-fresh cache-clear \
        tinker create-tenant routes pint test build-assets logs shell
