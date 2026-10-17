FROM dunglas/frankenphp:1-php8 AS base

ENV SERVER_NAME=:8000
ARG USER=appuser
ARG UID=1000
ARG GID=1000

WORKDIR /app

# ---------------------------- OS install ---------------------------- #
RUN apt update && apt install -y git tzdata \
    && rm -rf /var/cache/apt/*

# ---------- add non-root user
RUN groupadd --gid ${GID} ${USER} \
    && useradd --uid ${UID} --gid ${GID} --create-home ${USER} \
    && setcap -r /usr/local/bin/frankenphp \
    && chown -R ${USER}:${USER} /config/caddy /data/caddy

# Configure Timezone
# https://wiki.alpinelinux.org/wiki/Setting_the_timezone
RUN rm -f /etc/localtime && ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime

# ---------- Port
EXPOSE 8000

# ------------------------------------------------------------------------------
# PHP
#
# Frankenphp Use (similar of) install-php-extensions for easier PHP extension installation
# extension-installer - https://github.com/mlocati/docker-php-extension-installer
# apcu intl opcache => Symfony recommendation
# pgsql pdo_pgsql => Postgres Database
# ------------------------------------------------------------------------------
RUN install-php-extensions @composer-2 apcu intl opcache pgsql pdo_pgsql
COPY .docker/php.ini $PHP_INI_DIR/conf.d/z-01-php-app.ini

# ------------------------------------------------------------------------------
# 🧪 Development environment
# ------------------------------------------------------------------------------
FROM base AS dev
ARG USER=appuser

# ---------------------------- Add Symfony CLI
COPY --link --from=ghcr.io/symfony-cli/symfony-cli:latest /usr/local/bin/symfony /usr/local/bin/symfony

# Install Xdebug for development
RUN install-php-extensions xdebug
USER ${USER}

# ------------------------------------------------------------------------------
# 🚀 Production environment
# ------------------------------------------------------------------------------
FROM base AS prod

ARG USER=appuser
ENV APP_ENV="prod"

RUN chown -R ${USER}:${USER} /app
COPY --chown=${USER}:${USER} . /app
RUN mkdir -p /app/var /app/config/jwt && \
    chown -R ${USER}:${USER} /app/var /app/config/jwt

USER ${USER}
RUN composer app:build
