<?php
/**
 * Plugin service loader class.
 *
 * PHP Version 8.2
 *
 * @package    Bmd\FeaturedImageBlockFallback
 * @author     Bob Moore <bob@bobmoore.dev>
 * @license    GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link       https://www.bobmoore.dev
 * @since      1.0.0
 */

namespace Bmd\FeaturedImageBlockFallback;

use Bmd\FeaturedImageBlockFallback\Bmd\GithubWpUpdater;

/**
 * Service loader/locator class for the featured image block fallback plugin.
 */
class ServiceLoader
{
	/**
	 * Main plugin service instance.
	 *
	 * @var Plugin|null
	 */
	protected static ?Plugin $instance = null;

	/**
	 * Constructor.
	 *
	 * Initializes the plugin service and update checker once.
	 */
	public function __construct()
	{
		$plugin_file = dirname( __DIR__ ) . '/featured-image-block-fallback.php';

		if ( null === self::$instance ) {
			self::$instance = new Plugin(
				plugin_dir_url( $plugin_file ),
				plugin_dir_path( $plugin_file )
			);

			self::$instance->mount();

			$updater = new GithubWpUpdater(
				$plugin_file,
				[
					'github.user'   => 'bob-moore',
					'github.repo'   => 'Featured-Image-Block-Fallback',
					'github.branch' => 'main',
				]
			);

			$updater->mount();
		}
	}

	/**
	 * Get the initialized plugin service.
	 *
	 * @return Plugin|null Plugin service, or null before bootstrap has run.
	 */
	public static function getInstance(): ?Plugin
	{
		return self::$instance;
	}
}
