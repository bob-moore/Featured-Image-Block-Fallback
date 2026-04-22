<?php
/**
 * Plugin Name:       Featured Image Block Fallback
 * Plugin URI:        https://github.com/bob-moore/Featured-Image-Block-Fallback
 * Author:            Bob Moore
 * Author URI:        https://www.bobmoore.dev
 * Description:       Add fallback images to the featured image block
 * Version:           0.2.0
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

use Bmd\FeaturedImageBlockFallback;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

$plugin = new FeaturedImageBlockFallback();

$plugin->mount();