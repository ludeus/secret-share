# syntax=docker/dockerfile:1
#
# Secret Share - image Docker
#
#   docker build -t secret-share .                 # image de production
#   docker build --target test -t secret-share:test .  # image avec outils de test

ARG PHP_VERSION=8.3
# empreinte de php:${PHP_VERSION}-apache, mise à jour par maintenance/upgrade.py
ARG PHP_IMAGE_DIGEST=sha256:f8506a19d79cad7fcf0c8049f1ed2208824961018fdc10bd80f324094dcd36bd

# ---------------------------------------------------------------------------
# Dépendances Composer (sans les outils de développement)
# ---------------------------------------------------------------------------
FROM composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-autoloader --no-scripts
COPY src/ src/
RUN composer dump-autoload --no-dev --classmap-authoritative

# dépendances de développement, pour la cible « test »
FROM composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS vendor-dev
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts

# ---------------------------------------------------------------------------
# Base : Apache + PHP, durcis
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-apache@${PHP_IMAGE_DIGEST} AS base

# correctifs de sécurité Debian publiés depuis la construction de l'image officielle
RUN apt-get update \
    && apt-get upgrade -y --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# opcache pour les performances (intégré à PHP depuis la 8.5, extension à installer avant) ;
# sodium et pdo_sqlite sont fournis par l'image officielle
RUN (php -m | grep -qx 'Zend OPcache' || docker-php-ext-install -j"$(nproc)" opcache) \
    && a2enmod headers \
    && a2dismod -f autoindex status negotiation \
    && rm -f /etc/apache2/sites-enabled/*.conf /etc/apache2/conf-enabled/other-vhosts-access-log.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/apache/ports.conf /etc/apache2/ports.conf
COPY docker/apache/secret-share.conf /etc/apache2/sites-enabled/secret-share.conf
COPY docker/php/secret-share.ini "$PHP_INI_DIR/conf.d/zz-secret-share.ini"
COPY docker/entrypoint.sh /usr/local/bin/secret-share-entrypoint

# le serveur tourne sans privilèges : il n'écrit que dans /app/data et les dossiers d'exécution d'Apache
RUN mkdir -p /app/data \
    && chown -R www-data:www-data /app/data /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chmod 0700 /app/data

WORKDIR /app
ENV DATABASE_PATH=/app/data/secret-share.sqlite

EXPOSE 8080
VOLUME ["/app/data"]
STOPSIGNAL SIGWINCH
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/health || exit 1

ENTRYPOINT ["secret-share-entrypoint"]
CMD ["apache2-foreground"]

# ---------------------------------------------------------------------------
# Test : dépendances de développement, pour lancer « composer check » en CI
# ---------------------------------------------------------------------------
FROM base AS test
COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/bin/composer
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor-dev /app/vendor/ vendor/
RUN chown www-data:www-data /app
USER www-data
RUN composer dump-autoload
ENTRYPOINT []
CMD ["composer", "check"]

# ---------------------------------------------------------------------------
# Production (cible par défaut)
# ---------------------------------------------------------------------------
FROM base AS production

LABEL org.opencontainers.image.title="Secret Share" \
      org.opencontainers.image.description="Partage de secrets éphémères et chiffrés via un lien ou un QR code" \
      org.opencontainers.image.licenses="AGPL-3.0-or-later" \
      org.opencontainers.image.source="https://github.com/ludeus/secret-share"

COPY --from=vendor /app/vendor/ vendor/
COPY bin/ bin/
COPY lang/ lang/
COPY public/ public/
COPY src/ src/
COPY templates/ templates/
COPY LICENSE composer.json ./

USER www-data
