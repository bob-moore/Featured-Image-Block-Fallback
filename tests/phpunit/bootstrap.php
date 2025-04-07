<?php
/**
 * Bootstrap file for unit tests.
 *
 * @package Mwf\Cornerstone
 */
require_once dirname(  __FILE__ ) . '/wp-function-mocks.php';
require_once dirname( __DIR__, 2 ) . '/inc/Functions.php';

WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();