<?php
/**
 * Plugin Name:       Featured Image Block Fallback
 * Plugin URI:        https://bobmoore.dev
 * Author:            Bob Moore
 * Author URI:        https://www.bobmoore.dev
 * Description:       Add fallback images to the featured image block
 * Version:           0.1.4
 * Requires at least: 6.7
 * Tested up to:      6.7.2
 * Requires PHP:      8.2
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       featured-image-block-fallback
 *
 * @package           featured-image-block-fallback
 */

namespace MarkedEffect\FeaturedImageBlockFallback;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';


if ( class_exists( 'MarkedEffect\\FeaturedImageBlockFallback\\Plugin' ) ) {
	new Plugin();
	$updater = new Updater( __FILE__ );
}