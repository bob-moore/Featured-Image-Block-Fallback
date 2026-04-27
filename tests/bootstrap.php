<?php
/**
 * Bootstrap file for unit tests.
 *
 * @package Bmd\FeaturedImageBlockFallback
 */
require_once dirname( __DIR__, 1 ) . '/vendor/autoload.php';

require_once __DIR__ . '/wp-function-mocks.php';

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();
