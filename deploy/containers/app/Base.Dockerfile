#=========================================================================
#IMPORTANT set the same version for phpdbg in Develop.DockerFile
FROM php:8.1-fpm as base

COPY ./deploy/containers/app/php.ini /usr/local/etc/php/php.ini
## Install extensions
ENV EXTENSIONS_BUILD_DEPS \
        libicu-dev \
        libzip-dev \
        libxml2-dev \
#php-event start
        libevent-dev \
        libssl-dev \
#php-event end

ENV PHP_EXTS_BASE \
        intl \
        opcache \
        bcmath \
        zip \
        pcntl
ENV PHP_EXTS_APP \
        curl
# hadolint ignore=DL3008,SC2046
RUN cat /etc/os-release && apt-get update \
# install deps
    && apt-get install -y --no-install-recommends ${EXTENSIONS_BUILD_DEPS} \
        # extensions
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        	&& docker-php-ext-install -j$(nproc) gd\
        && docker-php-ext-install sockets && docker-php-ext-enable sockets\
            && pecl install event && docker-php-ext-enable event && mv /usr/local/etc/php/conf.d/docker-php-ext-event.ini /usr/local/etc/php/conf.d/z99docker-php-ext-event.ini \
        && docker-php-ext-install \
                ${PHP_EXTS_BASE} \
                ${PHP_EXTS_APP} \
            && docker-php-ext-enable \
                ${PHP_EXTS_BASE} \
                ${PHP_EXTS_APP} \
    # clean up
    && apt-get purge \
        -y --auto-remove \
        -o APT::AutoRemove::RecommendsImportant=false \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
