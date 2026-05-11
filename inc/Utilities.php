<?php
/**
 * Package utility helpers.
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

/**
 * Utility methods for resolving package paths and URLs.
 */
class Utilities
{
	/**
	 * Get the package root path.
	 *
	 * @return string Absolute normalized path to the package root.
	 */
	public static function getPath(): string
	{
		return wp_normalize_path( dirname( __DIR__ ) );
	}

	/**
	 * Get the package URL.
	 *
	 * @return string Absolute URL to the package root.
	 */
	public static function getUrl(): string
	{
		$path = wp_normalize_path( dirname( __DIR__ ) );

		return 'theme' === self::getUsageContext()
			? self::buildThemeUrl( $path )
			: plugin_dir_url( $path );
	}

	/**
	 * Build a theme URL for a package located inside the active theme.
	 *
	 * @param string $path Absolute package path.
	 *
	 * @return string Absolute URL to the package path.
	 */
	protected static function buildThemeUrl( string $path = '' ): string
	{
		$relative_path = str_replace(
			get_theme_file_path(),
			'',
			$path
		);

		return get_theme_file_uri( $relative_path );
	}

	/**
	 * Determine whether the package is loaded from a plugin or theme.
	 *
	 * @return string Either `plugin` or `theme`.
	 */
	protected static function getUsageContext(): string
	{
		$path = wp_normalize_path( dirname( __DIR__ ) );

		return str_contains( $path, get_theme_file_path() )
			? 'theme'
			: 'plugin';
	}
}
