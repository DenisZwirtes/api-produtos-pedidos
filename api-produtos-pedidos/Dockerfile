FROM php:8.2-cli

# Instalar extensões necessárias
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libssl-dev \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist

COPY . .

# Evitar erro de 'dubious ownership' do git quando volume é montado
RUN git config --global --add safe.directory /var/www/html

CMD sh -lc " \
    composer install --no-interaction && \
    php -r \"file_exists('.env') || copy('.env.example', '.env');\" && \
    php artisan key:generate --ansi && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8000 \
"


