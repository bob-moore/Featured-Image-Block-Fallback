<?php
/**
 * Plugin bootstrap.
 *
 * @wordpress-plugin
 * Plugin Name: Featured Image Block Fallback
 * Plugin URI:  https://github.com/bob-moore/Featured-Image-Block-Fallback
 * Description: Add fallback images to the featured image block.
 * Version:     0.3.4
 * Author:      Bob Moore
 * Author URI:  https://www.bobmoore.dev
 * Requires at least: 6.5
 * Tested up to: 7.0
 * Requires PHP: 8.2
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: featured-image-block-fallback
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback;

use Bmd\GithubWpUpdater;

defined( 'ABSPATH' ) || exit;

/**
 * Load dependencies and mount the plugin after WordPress has loaded plugins.
 *
 * @throws \RuntimeException If Composer dependencies are missing.
 *
 * @return void
 */
function load_plugin(): void
{
	try {
		$main_class        = Main::class;
		$scoped_autoload   = plugin_dir_path( __FILE__ ) . 'vendor/scoped/autoload.php';
		$scoper_autoload   = plugin_dir_path( __FILE__ ) . 'vendor/scoped/scoper-autoload.php';
		$composer_autoload = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

		// Stop before loading Composer if another copy of this package already booted.
		if ( class_exists( $main_class, false ) ) {
			throw new \RuntimeException( 'Featured Image Block Fallback is already loaded by another plugin. Deactivate the duplicate standalone plugin or remove the bundled dependency.' );
		}

		// Release builds include scoped dependencies; local development uses the normal Composer autoloader.
		if ( is_file( $scoped_autoload ) && is_file( $scoper_autoload ) ) {
			require_once $scoped_autoload;
			require_once $scoper_autoload;
		}

		if ( ! is_file( $composer_autoload ) ) {
			throw new \RuntimeException( 'Featured Image Block Fallback dependencies are missing. Run composer install before activating the plugin.' );
		}

		require_once $composer_autoload;

		$plugin = new Main(
			[
				'package' => 'featured_image_block_fallback',
				'version' => '0.3.4',
				'path'    => plugin_dir_path( __FILE__ ),
				'url'     => plugin_dir_url( __FILE__ ),
			]
		);
		$plugin->mount();
	} catch ( \Throwable $e ) {
		// Keep production logs quiet while still surfacing bootstrap issues during development.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $e->getMessage() );
		}
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin' );

/**
 * Initialize updates from GitHub.
 *
 * @return void
 */
function update_from_github(): void
{
	try {
		if ( ! class_exists( GithubWpUpdater::class ) ) {
			return;
		}

		$updater = new GithubWpUpdater(
			__FILE__,
			[
				'github.user'    => 'bob-moore',
				'github.repo'    => 'Featured-Image-Block-Fallback',
				'github.branch'  => 'main',
				'plugin.banners' => [
					'low'  => plugin_dir_url( __FILE__ ) . 'assets/banner-772x250.jpg',
					'high' => plugin_dir_url( __FILE__ ) . 'assets/banner-1544x500.jpg',
				],
				'plugin.icons'   => [
					'default' => plugin_dir_url( __FILE__ ) . 'assets/icon.jpg',
				],
			]
		);
		$updater->mount();
	} catch ( \Error $e ) {
		// Keep production logs quiet while still surfacing updater issues during development.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $e->getMessage() );
		}
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\update_from_github' );
