FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    curl zip unzip git libpng-dev libonig-dev libxml2-dev libzip-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml ctype fileinfo curl zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

RUN php artisan storage:link || true

EXPOSE 8080

CMD php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
