FROM php:8.2-fpm
COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /app

RUN composer self-update

RUN apt-get update && apt-get install -y unzip bash-completion
