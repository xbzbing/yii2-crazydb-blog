FROM nginx:1.19-alpine

ENV TZ Asia/Shanghai

# RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g' /etc/apk/repositories
RUN apk update && apk add bash tzdata curl


COPY config/nginx.conf /etc/nginx/nginx.conf

COPY config/conf.d/* /etc/nginx/conf.d/

RUN echo "0 0 * * * nginx -s reopen" >> /var/spool/cron/crontabs/root && echo "alias ll='ls -l'" >> /root/.bashrc

RUN mkdir -p /opt/webroot/web && mkdir -p /opt/log/nginx && mkdir -p /opt/html/default

COPY config/index.html /opt/html/default

WORKDIR /opt/webroot

VOLUME ["/opt/webroot", "/opt/log/nginx", "/etc/nginx/conf.d"]

EXPOSE 80 443
