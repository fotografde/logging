#=========================================================================
ARG FROM_BASE
# hadolint ignore=DL3006
FROM $FROM_BASE as base
#=========================================================================

#=========================================================================
# hadolint ignore=DL3006
FROM base as build

# hadolint ignore=DL3008
RUN apt-get update \
# install deps
    && apt-get install -y --no-install-recommends git ssh zip unzip wget libsqlite3-dev \
      && pecl install pcov \
        && docker-php-ext-install \
                pdo_sqlite \
            && docker-php-ext-enable \
                    pcov \
                    pdo_sqlite \
    && apt-get purge \
        -y --auto-remove \
        -o APT::AutoRemove::RecommendsImportant=false \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

#composer
# hadolint ignore=DL3022
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
#=========================================================================

#=========================================================================
FROM build as test

# Install extensions
#phpdbg
#IMPORTANT set the same version for phpdbg in Base.DockerFile
# hadolint ignore=DL3022
COPY --from=php:8.1-cli /usr/local/bin/phpdbg /usr/local/bin/

#xdebug
RUN  pecl install xdebug

COPY ./deploy/containers/app/xdebug.sh /usr/local/bin/xdebug
RUN chmod +x /usr/local/bin/xdebug
#=========================================================================

#=========================================================================
FROM test as develop

COPY ./deploy/containers/app/php-debug.ini /usr/local/etc/php/php.ini
# Enable extensions
RUN docker-php-ext-enable xdebug
#=========================================================================

