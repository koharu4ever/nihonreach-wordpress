<?php
/** Immutable production configuration; secrets are runtime environment variables. */
function nrc_env_required( $name ) {
	$value = getenv( $name );
	if ( false === $value || '' === $value ) { throw new RuntimeException( 'Missing required environment variable: ' . $name ); }
	return $value;
}
define( 'DB_NAME', nrc_env_required( 'WORDPRESS_DB_NAME' ) );
define( 'DB_USER', nrc_env_required( 'WORDPRESS_DB_USER' ) );
define( 'DB_PASSWORD', nrc_env_required( 'WORDPRESS_DB_PASSWORD' ) );
define( 'DB_HOST', nrc_env_required( 'WORDPRESS_DB_HOST' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$nrc_url = rtrim( nrc_env_required( 'NRC_SITE_URL' ), '/' );
$nrc_scheme = parse_url( $nrc_url, PHP_URL_SCHEME );
if ( 'https' !== $nrc_scheme && ! ( 'http' === $nrc_scheme && '1' === getenv( 'NRC_LOCAL_SMOKE_TEST' ) ) ) {
	throw new RuntimeException( 'NRC_SITE_URL must use HTTPS outside the isolated smoke test.' );
}
define( 'WP_HOME', $nrc_url );
define( 'WP_SITEURL', $nrc_url );
define( 'FORCE_SSL_ADMIN', 'https' === $nrc_scheme );
// This service has no host port: Coolify terminates TLS for the configured HTTPS URL.
if ( 'https' === $nrc_scheme ) { $_SERVER['HTTPS'] = 'on'; }
$nrc_secret = nrc_env_required( 'NRC_AUTH_SECRET' );
if ( strlen( $nrc_secret ) < 64 ) { throw new RuntimeException( 'NRC_AUTH_SECRET must contain at least 64 characters.' ); }
foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $nrc_key ) {
	define( $nrc_key, hash_hmac( 'sha256', $nrc_key, $nrc_secret ) );
}
unset( $nrc_secret, $nrc_key );
$table_prefix = 'wp_';
define( 'WP_DEBUG', false );
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );
define( 'WP_AUTO_UPDATE_CORE', false );
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
