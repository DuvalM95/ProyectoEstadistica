FROM php:8.2-cli

RUN apt-get update && apt-get install -y libcurl4-openssl-dev libonig-dev libxml2-dev && \
    docker-php-ext-install curl && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . /app

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
