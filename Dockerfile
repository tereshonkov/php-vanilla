FROM php:8.5-fpm

# Устанавливаем системные пакеты, нужные для сборки (zip, unzip, git нужны для Composer)
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Устанавливаем встроенные расширения: pdo_mysql и bcmath
RUN docker-php-ext-install pdo_mysql bcmath

# Устанавливаем через PECL и включаем сторонние расширения: Redis и Xdebug
RUN pecl install redis xdebug && docker-php-ext-enable redis xdebug

# Копируем Composer из официального образа
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Копируем файлы зависимостей и ставим их
# COPY composer.json ./
# RUN composer install --no-scripts --no-interaction

# Оптимальное кэширование слоев для продакшена (копируем только зависимости)
COPY composer.json composer.lock ./

# Копируем остальной код проекта
COPY . .

# Настраиваем права для веб-сервера (www-data)
RUN chown -R www-data:www-data /var/www

# Включаем clear_env = no что бы не стирались данные из env при запуске
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Открываем стандартный порт PHP-FPM
EXPOSE 9000

# Запускаем родной PHP-FPM сервер (Nginx подключится к нему)
CMD ["php-fpm"]