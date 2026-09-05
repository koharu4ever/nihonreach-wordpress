FROM wordpress:cli-php8.3@sha256:2b5e9d4d3e51909dca1aaa4732e9f5e5bf0377c2114dbd8ff39f060bff202586 AS wpcli
FROM wordpress:7.1-php8.3-apache@sha256:5a93c470ae8220fddf71f6ebe3bc94e615ddc2ae4d9810f795b830fb11c41a17

# Debian/glibc supports VS Code Server; reuse the site's PHP version/extensions.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git openssh-client unzip less \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /home/www-data \
    && usermod --home /home/www-data --shell /bin/bash www-data \
    && chown www-data:www-data /home/www-data \
    && git config --system --add safe.directory /work

COPY --from=wpcli /usr/local/bin/wp /usr/local/bin/wp

# Verify the official installer and pin Composer rather than tracking latest.
RUN set -eu; \
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php; \
    expected="$(curl -fsSL https://composer.github.io/installer.sig)"; \
    actual="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"; \
    test "$expected" = "$actual"; \
    php /tmp/composer-setup.php --version=2.10.3 --install-dir=/usr/local/bin --filename=composer; \
    rm /tmp/composer-setup.php

ENV HOME=/home/www-data
WORKDIR /work
USER www-data
ENTRYPOINT []
CMD ["sleep", "infinity"]
