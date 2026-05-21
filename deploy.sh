#!/bin/bash
# Ejecutar en el servidor después de cada: git pull
# Uso: bash deploy.sh

set -e
cd "$(dirname "$0")"

echo "==> Migraciones..."
php artisan migrate --force

echo "==> Vincular proveedores en pedidos..."
php artisan pedidos:sync-proveedores

echo "==> Limpiar caché..."
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "==> Listo."
