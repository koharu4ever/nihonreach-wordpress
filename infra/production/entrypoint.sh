#!/bin/sh
set -eu
mkdir -p /opt/nrc/site/wp-content/uploads
chown www-data:www-data /opt/nrc/site/wp-content/uploads
exec apache2-foreground
