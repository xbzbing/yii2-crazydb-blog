FROM mysql:5.7

ENV TZ Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

EXPOSE 3306 33060

VOLUME ["/var/lib/mysql"]
