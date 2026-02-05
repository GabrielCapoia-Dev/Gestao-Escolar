# Variáveis
DC = docker compose
APP_NAME = laravel-app-gestao-escolar

.PHONY: up down restart build migrate fresh shell

# Sobe o ambiente completo
up:
	$(DC) up -d

# Derruba o ambiente
down:
	$(DC) down

# Força o rebuild e sobe
build:
	$(DC) up -d --build

# Roda as migrações
migrate:
	$(DC) exec app php artisan migrate

# Reseta o banco de dados do zero
fresh:
	$(DC) exec app php artisan migrate:fresh --seed

# Abre o terminal dentro do container app
shell:
	$(DC) exec app sh