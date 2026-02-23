FROM dunglas/frankenphp:1.11-php8.5-trixie AS base

ENV TZ=UTC
ENV DEBIAN_FRONTEND=noninteractive

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

# add additional extensions here:
ARG PHP_EXTENSIONS="bcmath curl intl mbstring opcache pcntl pdo_sqlite pdo_pgsql pgsql readline sockets xml zip"
RUN install-php-extensions ${PHP_EXTENSIONS} @composer

# Install dependencies
RUN apt-get update && \
    apt-get install -y curl git mc htop sqlite3 postgresql-client && \
    apt-get -y autoremove && apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*


# Add NodeSource repository and install Node.js
ARG NODE_VERSION=22
RUN curl -fsSL https://deb.nodesource.com/setup_$NODE_VERSION.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && npm install -g pnpm@latest-10 \
    && npm install -g yarn \
    && npx playwright install-deps \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install lefthook
RUN curl -1sLf 'https://dl.cloudsmith.io/public/evilmartians/lefthook/setup.deb.sh' | bash \
    && apt-get update \
    && apt-get install -y lefthook \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY php.ini $PHP_INI_DIR/conf.d/99-custom.ini
RUN cp $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini


ARG DOCKER_GID=999
ARG DOCKER_UID=999
RUN groupadd -o -g $DOCKER_GID nonroot \
    && useradd -o -m -s /bin/bash -u $DOCKER_UID -g $DOCKER_GID nonroot

# ------------------------------------------------------
# XDG Base Directories + Composer clean setup
# ------------------------------------------------------
ENV XDG_CONFIG_HOME=/home/nonroot/.config \
    XDG_DATA_HOME=/home/nonroot/.local/share \
    XDG_CACHE_HOME=/home/nonroot/.cache

ENV COMPOSER_HOME=$XDG_DATA_HOME/composer \
    COMPOSER_CACHE_DIR=$XDG_CACHE_HOME/composer \
    PATH="$XDG_DATA_HOME/composer/vendor/bin:$PATH"

RUN mkdir -p $XDG_CONFIG_HOME $XDG_DATA_HOME $XDG_CACHE_HOME && \
    mkdir -p $COMPOSER_HOME/vendor/bin && \
    chown -R nonroot:nonroot /home/nonroot

ENV HOST="0.0.0.0"
WORKDIR /app
USER nonroot

FROM base AS worker
HEALTHCHECK --interval=30s --timeout=5s --start-period=3s --retries=3 \
    CMD pgrep -f "artisan epsicube:work" || exit 1
CMD ["php", "artisan", "epsicube:work"]

FROM base AS web
ENV SERVER_NAME=":80"
ENV SERVER_ROOT="/app/public"

HEALTHCHECK --interval=30s --timeout=5s --start-period=3s --retries=3 \
    CMD curl -f -s -o /dev/null -w "%{http_code}" http://localhost/up | grep -qE "200|404" || exit 1

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
