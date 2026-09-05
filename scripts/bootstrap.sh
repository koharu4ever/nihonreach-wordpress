#!/bin/sh
set -eu
url="${1:-http://127.0.0.1:8081}"
if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url="$url" --title=NihonReach --admin_user="$NRC_ADMIN_USER" --admin_password="$NRC_ADMIN_PASSWORD" --admin_email=demo@example.test --skip-email
fi
wp language core install zh_CN --activate
if ! wp theme is-installed twentytwentyone; then
  wp theme install twentytwentyone --version=2.9
fi
wp language theme install twentytwentyone zh_CN
wp theme activate nrc-child
wp plugin activate nrc-catalog
wp eval-file /work/scripts/seed.php
wp rewrite flush --hard
