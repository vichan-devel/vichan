FROM redis:7.4-alpine

RUN mkdir -p /var/run/redis && chmod 777 /var/run/redis
COPY ./docker/redis/redis.conf /etc/redis.conf

ENTRYPOINT [ "docker-entrypoint.sh", "/etc/redis.conf" ]
