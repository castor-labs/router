FROM alpine:3.14

# Composer Version
ARG COMPOSER_VERSION=2.1.9
ARG UID=1000

# Install Base PHP
RUN apk add --no-cache wget php8 \
    php8-curl \
    php8-phar \
    php8-json \
    php8-zip \
    php8-xml \
    php8-xmlreader \
    php8-xmlwriter \
    php8-sockets \
    php8-session \
    php8-bcmath \
    php8-fileinfo \
    php8-simplexml \
    php8-tokenizer \
    php8-iconv \
    php8-sodium \
    php8-mbstring \
    php8-openssl \
    php8-pcntl \
    php8-posix \
    php8-pecl-xdebug

# Link PHP
RUN ln -s /usr/bin/php8 /usr/bin/php

# Configure XDebug
COPY ./main/ini/60_xdebug.ini /etc/php8/conf.d/60_xdebug.ini

# Install Composer
RUN wget -O /usr/bin/composer https://github.com/composer/composer/releases/download/$COMPOSER_VERSION/composer.phar && \
    chmod +x /usr/bin/composer

# Add User
RUN adduser -h /castor -D -u $UID castor

USER castor

# Long running dummy process
CMD ["tail", "-f", "/dev/null"]