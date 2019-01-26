FROM redis:5-alpine

ENV TZ Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

EXPOSE 6379

CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]
