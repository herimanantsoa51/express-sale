FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev zip unzip \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copier tout le projet (vendor inclus)
COPY . .

COPY .env.docker .env

RUN php artisan key:generate --force \
 && php artisan config:clear \
 && php artisan config:cache \
 && php artisan route:cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
