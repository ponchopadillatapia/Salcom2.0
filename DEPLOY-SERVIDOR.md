# Deploy en SiteGround (salcomlink.mx)

Subes código con **git push** (local) + **git pull** (servidor). Eso solo actualiza archivos PHP; **no actualiza la base de datos**.

## Después de cada `git pull` en el servidor

```bash
cd ~/www/salcomlink.mx/public_html
git pull
bash deploy.sh
```

O manualmente:

```bash
php artisan migrate --force
php artisan pedidos:sync-proveedores
php artisan config:clear
php artisan view:clear
```

## Si aún no hay proveedores en la BD

```bash
php artisan db:seed --class=ProveedorUserSeeder --force
php artisan pedidos:sync-proveedores
```

## En tu PC (antes del pull en servidor)

Asegúrate de haber hecho push de todo:

```bash
git add .
git commit -m "Sync proveedores en pedidos y script deploy"
git push
```
