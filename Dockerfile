FROM php:8.4-cli
WORKDIR /app
RUN apt-get update && apt-get install -y git unzip nodejs npm && rm -rf /var/lib/apt/lists/*
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .
RUN cp .env.example .env 2>/dev/null || echo "APP_KEY=" > .env
RUN echo "SESSION_DRIVER=cookie" >> .env && echo "CACHE_STORE=array" >> .env && echo "QUEUE_CONNECTION=sync" >> .env && echo "ASSET_URL=https://mi-servicio-lti-713107332501.us-central1.run.app" >> .env
RUN composer install --no-dev --optimize-autoloader && npm ci && npm run build
RUN php artisan key:generate && php artisan optimize && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8080
CMD php artisan serve --host=0.0.0.0 --port=8080
