<?php
/** Local demo: prevent real email delivery. */
defined( 'ABSPATH' ) || exit;
add_filter( 'pre_wp_mail', '__return_true' );
add_filter( 'automatic_updater_disabled', '__return_true' );
