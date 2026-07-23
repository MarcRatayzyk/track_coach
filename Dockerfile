FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev \
    nodejs npm \
    && docker-php-ext-install pdo pdo_pgsql mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php-overrides.ini /usr/local/etc/php/conf.d/99-track-coach.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# PostHog / Reverb : les VITE_* au build sont optionnelles.
# Préférer POSTHOG_KEY au runtime (injecté via blade) — fiable sur Render Docker.
ARG VITE_POSTHOG_KEY=
ARG VITE_POSTHOG_HOST=https://eu.i.posthog.com
ENV VITE_POSTHOG_KEY=$VITE_POSTHOG_KEY
ENV VITE_POSTHOG_HOST=$VITE_POSTHOG_HOST

RUN composer dump-autoload --optimize
RUN node scripts/generate-pwa-icons.mjs
RUN npm run build

RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/{sessions,views} storage/logs \
    && chmod -R 775 storage bootstrap/cache

CMD rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php && \
    php artisan package:discover --ansi && \
    php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-10000}