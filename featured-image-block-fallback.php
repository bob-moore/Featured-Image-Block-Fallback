<?php
/**
 * Plugin Name:       Featured Image Block Fallback
 * Plugin URI:        https://bobmoore.dev
 * Author:            Bob Moore
 * Author URI:        https://www.bobmoore.dev
 * Description:       Add fallback images to the featured image block
 * Version:           0.1.5
 * Requires at least: 6.5
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

use MarkedEffect\FeaturedImageBlockFallback\Deps;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/scoped/autoload.php';
require_once __DIR__ . '/vendor/scoped/scoper-autoload.php';

/**
 * Run the plugin. Simple.
 *
 * @return void
 */
function run() {
	new Plugin();
}
run();
/**
 * Init the github updater.
 *
 * @return void
 */
function init_updater() {
	$plugin_args = [
		'github.user'    => 'bob-moore',
		'github.repo'    => 'Featured-Image-Block-Fallback',
		'github.branch'  => 'main',
		'config.banners' => [
			'low'  => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-772x250.jpg',
			'high' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-1544x500.jpg',
		],
		'config.icons' => [
			'default'  => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/icon.png',
		]
	];
	new Deps\MarkedEffect\GHPluginUpdater\Main( __FILE__, $plugin_args);
}
init_updater();