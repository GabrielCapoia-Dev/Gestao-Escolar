#!/bin/bash

# Cria pastas necessárias do Laravel
mkdir -p /var/www/storage/framework/{sessions,views,cache}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Ajusta permissões
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Instala SEM rodar scripts
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor)" ]; then
    echo "Pasta vendor vazia. Instalando dependências..."
    composer install --no-interaction --prefer-dist --no-scripts
fi

# Agora roda os scripts manualmente (pastas já existem)
composer dump-autoload
php artisan package:discover --ansi

if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate
fi

# Aguarda o banco estar acessível
echo "Aguardando banco de dados..."
until php artisan db:monitor --databases=mysql > /dev/null 2>&1; do
    sleep 2
done

# Verifica se já existe alguma tabela (exceto migrations)
TABLE_COUNT=$(php artisan tinker --execute="echo \DB::select(\"SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'gestao-escolar' AND table_name != 'migrations'\")[0]->count;" 2>/dev/null)

if [ "$TABLE_COUNT" -eq 0 ] 2>/dev/null || [ -z "$TABLE_COUNT" ]; then
    echo "Banco vazio. Rodando migrate --seed..."
    php artisan migrate --seed --force
else
    echo "Banco já populado. Rodando migrate..."
    php artisan migrate --force
fi

echo "Ambiente pronto! Iniciando comando: $@"
exec "$@"