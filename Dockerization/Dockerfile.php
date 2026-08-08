FROM webdevops/php-nginx:8.2

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev && \
    docker-php-ext-install pdo_pgsql pgsql && \
    rm -rf /var/lib/apt/lists/*

COPY Dockerization/nginx/vhost.common.d/ /opt/docker/etc/nginx/vhost.common.d/

ENV WEB_DOCUMENT_ROOT="/app/public"
