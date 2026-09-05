#!/bin/sh
set -eu
cd /opt/nrc/site
if wp core is-installed >/dev/null 2>&1; then
  echo 'Existing installation detected; no accounts, content or settings changed.'
  exit 0
fi
: "${NRC_ADMIN_USER:?Set a dedicated production admin username}"
: "${NRC_ADMIN_PASSWORD:?Set a dedicated production admin password}"
: "${NRC_ADMIN_EMAIL:?Set the admin email}"
wp core install --url="$NRC_SITE_URL" --title=NihonReach --admin_user="$NRC_ADMIN_USER" --admin_password="$NRC_ADMIN_PASSWORD" --admin_email="$NRC_ADMIN_EMAIL" --skip-email --locale=zh_CN
wp theme activate nrc-child
wp plugin activate nrc-catalog
wp --user="$NRC_ADMIN_USER" eval-file /opt/nrc/seed.php
wp rewrite flush
echo 'Production demo initialized. Remove NRC_ADMIN_PASSWORD from the deployment environment after recording it privately.'
