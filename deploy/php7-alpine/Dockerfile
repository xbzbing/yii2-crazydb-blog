FROM php:7.2-fpm-alpine

ENV TZ Asia/Shanghai
ENV ZOOKEEPER_VERSION 3.4.13

# RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g' /etc/apk/repositories
RUN apk update && apk add bash tzdata curl
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apk add autoconf gcc g++ make libffi-dev openldap-dev curl-dev libjpeg-turbo-dev \
    libjpeg-turbo freetype-dev jpeg-dev libpng-dev libmcrypt-dev libmemcached-dev icu-dev cyrus-sasl-dev libxml2-dev

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install intl mysqli pdo_mysql opcache bcmath zip ldap soap exif

RUN pecl channel-update pecl.php.net \
    && pecl install msgpack && docker-php-ext-enable msgpack \
    && pecl install igbinary && docker-php-ext-enable igbinary \
    && echo "yes"|pecl install redis && docker-php-ext-enable redis \
    && echo "yes"|pecl install memcached && docker-php-ext-enable memcached

RUN mkdir /download && cd /download && wget http://mirrors.hust.edu.cn/apache/zookeeper/zookeeper-$ZOOKEEPER_VERSION/zookeeper-$ZOOKEEPER_VERSION.tar.gz \
    && tar zxvf zookeeper-$ZOOKEEPER_VERSION.tar.gz  && cd zookeeper-$ZOOKEEPER_VERSION/src/c \
    && ./configure && make && make install

RUN pecl install zookeeper && docker-php-ext-enable zookeeper && rm -rf /download

# php config
ADD config/php.ini /usr/local/etc/php/php.ini
ADD config/php-fpm.conf /usr/local/etc/php-fpm.conf

WORKDIR /opt/webroot

VOLUME ["/opt/webroot", "/opt/log", "/opt/log/php"]

EXPOSE 9000
