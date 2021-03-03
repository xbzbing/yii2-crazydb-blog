FROM php:7.4-fpm-alpine

ENV TZ Asia/Shanghai

#RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g' /etc/apk/repositories
RUN apk update && apk add bash tzdata curl git

RUN apk add autoconf gcc g++ make libffi-dev openldap-dev curl-dev libjpeg-turbo-dev libwebp-dev libzip-dev\
    libjpeg-turbo freetype-dev jpeg-dev libpng-dev libmcrypt-dev libmemcached-dev icu-dev cyrus-sasl-dev libxml2-dev

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && sed -i 's/expose_php = On/expose_php = Off/' /usr/local/etc/php/php.ini

RUN docker-php-ext-configure gd --with-webp=/usr/include/webp --with-jpeg=/usr/include --with-freetype=/usr/include/freetype2/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install intl mysqli pdo_mysql opcache bcmath zip ldap soap exif

RUN pecl channel-update pecl.php.net \
    && pecl install msgpack && docker-php-ext-enable msgpack \
    && pecl install igbinary && docker-php-ext-enable igbinary \
    && echo "yes"|pecl install redis && docker-php-ext-enable redis \
    && echo "yes"|pecl install memcached && docker-php-ext-enable memcached

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer
RUN echo "alias ll='ls -l'" >> /root/.bashrc

WORKDIR /opt/webroot

VOLUME ["/opt/webroot", "/opt/log", "/opt/log/php"]

EXPOSE 9000
