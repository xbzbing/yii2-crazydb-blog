FROM nginx:1.30-alpine

ENV TZ Asia/Shanghai

RUN apk update && apk add bash tzdata curl logrotate

COPY config/nginx.conf /etc/nginx/nginx.conf
COPY config/conf.d/* /etc/nginx/conf.d/
COPY config/logrotate.conf /etc/logrotate.d/nginx

RUN mkdir -p /var/log/nginx /opt/webroot/web /opt/html/default

COPY config/index.html /opt/html/default

WORKDIR /opt/webroot

EXPOSE 80 443
