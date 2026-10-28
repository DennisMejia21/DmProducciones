FROM php:8.3-cli
RUN --mount=type=secret,id=proxy_ca,target=/run/secrets/proxy_ca \
    if [ -s /run/secrets/proxy_ca ]; then cp /run/secrets/proxy_ca /usr/local/share/ca-certificates/session-proxy.crt; update-ca-certificates; fi \
    && apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install -j2 pdo_mysql curl \
    && rm -rf /var/lib/apt/lists/* /usr/local/share/ca-certificates/session-proxy.crt \
    && update-ca-certificates --fresh
WORKDIR /app
COPY . .
USER www-data
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
