FROM nginx:1.15-alpine

ENV TZ Asia/Shanghai

# RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g' /etc/apk/repositories
RUN apk update && apk add bash tzdata curl
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone


COPY config/nginx.conf /etc/nginx/nginx.conf

COPY config/conf.d/* /etc/nginx/conf.d/


RUN mkdir -p /opt/webroot/web && mkdir -p /opt/log/nginx && mkdir -p /opt/html/default

COPY config/index.html /opt/html/default

WORKDIR /opt/webroot

VOLUME ["/opt/webroot", "/opt/log/nginx", "/etc/nginx/conf.d"]

EXPOSE 80 443
