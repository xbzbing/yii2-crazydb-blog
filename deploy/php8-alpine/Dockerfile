# ============================================================
# PHP 8.5 FPM 镜像（alpine）
# 运行用户：compose 中 php 服务以宿主机 uid:gid（默认 1001:1001）运行，
# 非 root 启动时 FPM 忽略 www.conf 的 user 指令，worker 即为该 uid，
# 对 bind mount 的源码目录保持写权限。宿主机 uid 不同时覆盖
# compose 的 user 即可，无需改镜像。
# ============================================================
FROM php:8.5-fpm-alpine

ENV TZ Asia/Shanghai

#RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g' /etc/apk/repositories
RUN apk update && apk add bash tzdata curl git

RUN apk add autoconf gcc g++ make libffi-dev openldap-dev curl-dev libjpeg-turbo-dev libwebp-dev libzip-dev\
    libjpeg-turbo freetype-dev jpeg-dev libpng-dev icu-dev libxml2-dev

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && sed -i 's/expose_php = On/expose_php = Off/' /usr/local/etc/php/php.ini

RUN docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install intl mysqli pdo_mysql bcmath zip ldap soap exif

RUN pecl channel-update pecl.php.net \
    && pecl install msgpack && docker-php-ext-enable msgpack \
    && pecl install igbinary && docker-php-ext-enable igbinary \
    && echo "yes"|pecl install redis && docker-php-ext-enable redis

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer
RUN echo "alias ll='ls -l'" >> /root/.bashrc

WORKDIR /opt/webroot

VOLUME ["/opt/webroot", "/opt/log", "/opt/log/php"]

EXPOSE 9000
