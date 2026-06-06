FROM dunglas/frankenphp:1.11-php8.5-trixie

ENV TZ=UTC
ENV DEBIAN_FRONTEND=noninteractive

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update && \
    apt-get install -y bash curl git sqlite3 postgresql-client && \
    apt-get -y autoremove && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ARG PHP_EXTENSIONS="bcmath curl intl mailparse mbstring opcache pcntl pcov pdo_sqlite pdo_pgsql pgsql readline sockets xml zip"
RUN install-php-extensions ${PHP_EXTENSIONS} @composer

RUN cp $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

WORKDIR /app
