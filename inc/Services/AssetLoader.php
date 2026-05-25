<?php
/**
 * Abstract asset loader service.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback\Services;

use Bmd\FeaturedImageBlockFallback\Module;

/**
 * Base class for script and style loaders.
 *
 * Injects the path and URL resolvers so concrete loaders can resolve assets
 * relative to the package root regardless of whether the package is loaded
 * as a plugin or composer-embedded inside a theme.
 */
abstract class AssetLoader extends Module
{
	/**
	 * Constructor.
	 *
	 * @param FilePathResolver $path_resolver File path resolver.
	 * @param UrlResolver      $url_resolver  URL resolver.
	 */
	public function __construct(
		protected FilePathResolver $path_resolver,
		protected UrlResolver $url_resolver,
	) {
	}

	/**
	 * Resolve script dependency metadata from WordPress build asset files.
	 *
	 * @param string $key Build asset key without the `.asset.php` suffix.
	 *
	 * @return array{dependencies: array<int, string>, version: string|null}
	 */
	public function getAssetData( string $key ): array
	{
		$asset_file = $this->path_resolver->resolve( "build/{$key}.asset.php" );

		if ( ! is_file( $asset_file ) ) {
			return [
				'dependencies' => [],
				'version'      => null,
			];
		}

		$data = include $asset_file;

		if ( ! is_array( $data ) ) {
			return [
				'dependencies' => [],
				'version'      => null,
			];
		}

		$dependencies = $data['dependencies'] ?? [];
		$version      = $data['version'] ?? filemtime( $asset_file );

		return [
			'dependencies' => is_array( $dependencies ) ? $dependencies : [],
			'version'      => is_string( $version ) ? $version : null,
		];
	}
}
