FROM wordpress:7.1-php8.3-apache@sha256:5a93c470ae8220fddf71f6ebe3bc94e615ddc2ae4d9810f795b830fb11c41a17 AS core
FROM wordpress:cli-php8.3@sha256:2b5e9d4d3e51909dca1aaa4732e9f5e5bf0377c2114dbd8ff39f060bff202586 AS wpcli
FROM php:8.3.33-apache-trixie@sha256:060ed9c0f6e4bbe4f8b25a34ca1ec596b96d8f4011cf7ee7eb6b7eecf01cb74f AS production

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev libicu-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j2 gd mysqli exif intl opcache zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=core /usr/src/wordpress/ /opt/nrc/site/
COPY --from=wpcli /usr/local/bin/wp /usr/local/bin/wp
RUN set -eu; \
    curl -fsSL https://downloads.wordpress.org/theme/twentytwentyone.2.9.zip -o /tmp/theme.zip; \
    unzip -q /tmp/theme.zip -d /opt/nrc/site/wp-content/themes; \
    mkdir -p /opt/nrc/site/wp-content/languages/themes; \
    curl -fsSL https://downloads.wordpress.org/translation/core/7.1/zh_CN.zip -o /tmp/core-zh.zip; \
    unzip -q /tmp/core-zh.zip -d /opt/nrc/site/wp-content/languages; \
    curl -fsSL https://downloads.wordpress.org/translation/theme/twentytwentyone/1.0/zh_CN.zip -o /tmp/theme-zh.zip; \
    unzip -q /tmp/theme-zh.zip -d /opt/nrc/site/wp-content/languages/themes; \
    rm /tmp/theme.zip /tmp/core-zh.zip /tmp/theme-zh.zip
COPY plugins/nrc-catalog/ /opt/nrc/site/wp-content/plugins/nrc-catalog/
COPY themes/nrc-child/ /opt/nrc/site/wp-content/themes/nrc-child/
COPY infra/local-only.php /opt/nrc/site/wp-content/mu-plugins/demo-policy.php
COPY infra/production/wp-config.php /opt/nrc/site/wp-config.php
COPY infra/production/health.php /opt/nrc/site/health.php
COPY infra/production/apache.conf /etc/apache2/sites-available/000-default.conf
COPY infra/production/php.ini /usr/local/etc/php/conf.d/nrc-production.ini
COPY infra/production/entrypoint.sh /usr/local/bin/nrc-entrypoint
COPY infra/production/initialize.sh /opt/nrc/initialize.sh
COPY scripts/seed.php /opt/nrc/seed.php
COPY assets/ /work/assets/
RUN chmod +x /usr/local/bin/nrc-entrypoint /opt/nrc/initialize.sh \
    && mkdir -p /opt/nrc/site/wp-content/uploads \
    && chown www-data:www-data /opt/nrc/site/wp-content/uploads
WORKDIR /opt/nrc/site
ENV WP_CLI_CACHE_DIR=/tmp/wp-cli-cache
EXPOSE 80
ENTRYPOINT ["nrc-entrypoint"]
