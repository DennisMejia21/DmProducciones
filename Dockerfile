FROM php:8.3-cli
RUN docker-php-ext-install -j2 pdo_mysql
WORKDIR /app
COPY . .
USER www-data
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
