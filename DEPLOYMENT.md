# Guía de Despliegue - Chatbot con IA para Canvas LMS

## 🚀 Despliegue en Producción

### Opción 1: Servidor Tradicional (VPS/Dedicated)

#### Requisitos del Servidor

- Ubuntu 22.04 LTS o superior
- Nginx o Apache
- PHP 8.2+ con extensiones: mbstring, xml, curl, zip, sqlite3/mysql
- Composer
- Node.js 18+
- SSL/TLS Certificate (Let's Encrypt)

#### Pasos de Instalación

```bash
# 1. Actualizar sistema
sudo apt update && sudo apt upgrade -y

# 2. Instalar PHP y extensiones
sudo apt install php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-sqlite3 -y

# 3. Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 4. Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y

# 5. Instalar Nginx
sudo apt install nginx -y

# 6. Clonar repositorio
cd /var/www
sudo git clone <repository-url> chatbot-ia
cd chatbot-ia

# 7. Configurar permisos
sudo chown -R www-data:www-data /var/www/chatbot-ia
sudo chmod -R 755 /var/www/chatbot-ia/storage

# 8. Instalar dependencias
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 9. Configurar .env
cp .env.example .env
php artisan key:generate

# 10. Ejecutar migraciones
php artisan migrate --force

# 11. Optimizar aplicación
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Configuración de Nginx

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name tu-dominio.com;
    root /var/www/chatbot-ia/public;

    ssl_certificate /etc/letsencrypt/live/tu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tu-dominio.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Activar configuración
sudo ln -s /etc/nginx/sites-available/chatbot-ia /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Configurar SSL con Let's Encrypt
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d tu-dominio.com
```

### Opción 2: Docker

```bash
# 1. Construir imagen
docker build -t chatbot-ia .

# 2. Ejecutar contenedor
docker run -d \
  -p 80:80 \
  -v $(pwd)/.env:/var/www/html/.env \
  -v $(pwd)/storage:/var/www/html/storage \
  --name chatbot-ia \
  chatbot-ia

# 3. Ejecutar migraciones
docker exec chatbot-ia php artisan migrate --force
```

### Opción 3: Laravel Forge

1. Conecta tu servidor en Laravel Forge
2. Crea un nuevo sitio con el repositorio
3. Configura las variables de entorno
4. Ejecuta el script de despliegue
5. Configura SSL automáticamente

### Opción 4: Plataformas Cloud

#### Railway.app

```bash
# railway.json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT",
    "healthcheckPath": "/",
    "healthcheckTimeout": 100,
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

#### Heroku

```bash
# Procfile
web: vendor/bin/heroku-php-apache2 public/
```

## 🔧 Configuración Post-Despliegue

### Variables de Entorno Críticas

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

OPENAI_API_KEY=sk-tu-clave-api-real
OPENAI_MODEL=gpt-4o-mini

DB_CONNECTION=mysql
DB_HOST=tu-servidor-db
DB_DATABASE=chatbot_ia
DB_USERNAME=usuario_db
DB_PASSWORD=contraseña_segura

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=localhost
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Configurar Supervisor (Workers)

```ini
# /etc/supervisor/conf.d/chatbot-worker.conf
[program:chatbot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/chatbot-ia/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/chatbot-ia/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chatbot-worker:*
```

### Configurar Cron Jobs

```bash
# Editar crontab
sudo crontab -e

# Añadir:
* * * * * cd /var/www/chatbot-ia && php artisan schedule:run >> /dev/null 2>&1
```

## 🔒 Seguridad

### Checklist de Seguridad

- [ ] SSL/TLS configurado (HTTPS)
- [ ] APP_DEBUG=false en producción
- [ ] Variables de entorno seguras
- [ ] Permisos de archivos correctos (755 para directorios, 644 para archivos)
- [ ] Firewall configurado (solo puertos 80, 443, 22)
- [ ] Backups automáticos configurados
- [ ] Rate limiting activado
- [ ] CORS configurado correctamente
- [ ] Headers de seguridad configurados

### Rate Limiting

```php
// app/Http/Kernel.php - ya incluido por defecto
'api' => [
    'throttle:60,1',
],
```

## 📊 Monitoreo

### Logs

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver últimos 100 errores
tail -n 100 storage/logs/laravel.log | grep ERROR
```

### Herramientas Recomendadas

- **Laravel Telescope**: Debugging local
- **Sentry**: Monitoreo de errores
- **New Relic**: Performance monitoring
- **Laravel Horizon**: Monitoreo de queues

## 🔄 Proceso de Actualización

```bash
# 1. Hacer backup
php artisan backup:run

# 2. Poner en modo mantenimiento
php artisan down

# 3. Actualizar código
git pull origin main

# 4. Actualizar dependencias
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 5. Ejecutar migraciones
php artisan migrate --force

# 6. Limpiar cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Reiniciar workers
php artisan queue:restart

# 8. Salir de modo mantenimiento
php artisan up
```

## 💾 Backups

### Backup Manual

```bash
# Base de datos
php artisan backup:run --only-db

# Completo (DB + archivos)
php artisan backup:run
```

### Backup Automático

Instalar Laravel Backup:

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Configurar cron:

```bash
# Backup diario a las 2 AM
0 2 * * * cd /var/www/chatbot-ia && php artisan backup:run >> /dev/null 2>&1
```

## 🐛 Troubleshooting

### Error 500

```bash
# Verificar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Verificar logs
tail -f storage/logs/laravel.log
```

### Problemas con OpenAI

```bash
# Verificar configuración
php artisan config:show openai

# Test de conexión
php artisan tinker
>>> OpenAI::models()->list();
```

### Problemas con LTI

- Verificar que Canvas pueda acceder a tu servidor
- Revisar configuración de CORS
- Verificar que las URLs sean HTTPS
- Revisar logs de Canvas y tu aplicación

## 📚 Recursos

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Nginx Configuration Guide](https://www.nginx.com/resources/wiki/)
- [Let's Encrypt](https://letsencrypt.org/)
- [Laravel Forge](https://forge.laravel.com/)
