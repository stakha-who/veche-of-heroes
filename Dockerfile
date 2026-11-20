FROM php:8.4-cli

RUN docker-php-ext-install pdo_mysql
WORKDIR /app
COPY . /app
CMD ["sh", "-c", "php bin/migrate.php && php bin/seed.php && exec php -S 0.0.0.0:8080 -t public public/index.php"]
