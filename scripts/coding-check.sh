#!/bin/sh
set -eu
cd /work
qa_dir="${NRC_QA_DIR:-/work/.cache/qa}"
mkdir -p "$qa_dir"
cd "$qa_dir"
if ! command -v composer >/dev/null 2>&1 && [ ! -f composer.phar ]; then
  curl -fsS https://getcomposer.org/installer -o composer-setup.php
  expected=$(curl -fsS https://composer.github.io/installer.sig)
  actual=$(php -r "echo hash_file('sha384', 'composer-setup.php');")
  [ "$expected" = "$actual" ] || exit 1
  php composer-setup.php --quiet
fi
cp /work/tests/composer.json /work/tests/composer.lock .
if command -v composer >/dev/null 2>&1; then
  composer install --no-interaction --no-progress
else
  php composer.phar install --no-interaction --no-progress
fi
vendor/bin/phpcs --standard=/work/tests/phpcs.xml /work/plugins /work/themes
