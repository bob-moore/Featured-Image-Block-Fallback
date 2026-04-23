<?php
/**
 * Plugin Name:       Featured Image Block Fallback
 * Plugin URI:        https://github.com/bob-moore/Featured-Image-Block-Fallback
 * Author:            Bob Moore
 * Author URI:        https://www.bobmoore.dev
 * Description:       Add fallback images to the featured image block
 * Version:           0.3.1
 * Requires at least: 6.5
 * Tested up to:      6.7.2
 * Requires PHP:      8.2
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       featured-image-block-fallback
 *
 * @package           featured-image-block-fallback
 */

use Bmd\FeaturedImageBlockFallback;
use Bmd\ResponsiveGridExtension\Bmd\GithubWpUpdater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/scoped/autoload.php';

function create_featured_image_block_fallback_plugin(): void
{
	$updater = new GithubWpUpdater(
		__FILE__,
		[
			'github.user'   => 'bob-moore',
			'github.repo'   => 'Featured-Image-Block-Fallback',
			'github.branch' => 'main',
		]
	);

	$plugin = new FeaturedImageBlockFallback(
		plugin_dir_url( __FILE__ ),
		plugin_dir_path( __FILE__ )
	);

	$updater->mount();
	$plugin->mount();
}
create_featured_image_block_fallback_plugin();
