<?php
/**
 * Style asset loader service.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback\Services;

/**
 * Handles registration and enqueueing of CSS stylesheets.
 *
 * Uses the injected resolvers so the package works as both a standalone
 * plugin and a composer dependency inside a theme.
 */
class StyleLoader extends AssetLoader
{
	/**
	 * Register a stylesheet, auto-resolving version from the companion
	 * .asset.php when the source is a local file.
	 *
	 * @param string            $handle       Style handle.
	 * @param string            $src          Relative path or full URL.
	 * @param array<int,string> $dependencies Style dependencies.
	 * @param string            $version      Version override.
	 * @param string            $screens      Media query / screen target.
	 *
	 * @return bool
	 */
	public function register(
		string $handle,
		string $src = '',
		array $dependencies = [],
		string $version = '',
		string $screens = 'all'
	): bool {
		$file = $this->path_resolver->resolve( $src );

		if ( is_file( $file ) && ! filesize( $file ) ) {
			return false;
		}

		if ( is_file( $file ) ) {
			if ( empty( $version ) ) {
				$assets  = $this->getAssetData( str_replace( '.css', '', basename( $src ) ) );
				$version = ! empty( $assets['version'] ) ? $assets['version'] : filemtime( $file );
			}

			$src = $this->url_resolver->resolve( $src );
		}

		return wp_register_style(
			$handle,
			$src,
			apply_filters( "{$this->package}_style_dependencies_{$handle}", $dependencies ),
			$version,
			$screens
		);
	}

	/**
	 * Register and enqueue a stylesheet.
	 *
	 * @param string            $handle       Style handle.
	 * @param string            $src          Relative path or full URL.
	 * @param array<int,string> $dependencies Style dependencies.
	 * @param string            $version      Version override.
	 * @param string            $screens      Media query / screen target.
	 *
	 * @return bool
	 */
	public function enqueue(
		string $handle,
		string $src = '',
		array $dependencies = [],
		string $version = '',
		string $screens = 'all'
	): bool {
		$registered = $this->register( $handle, $src, $dependencies, $version, $screens );

		if ( ! $registered ) {
			return false;
		}

		wp_enqueue_style( $handle );

		return true;
	}

	/**
	 * Enqueue a block-specific stylesheet via wp_enqueue_block_style.
	 *
	 * Skips silently when the file does not exist or is empty.
	 *
	 * @param string            $block_name   Fully-qualified block name (e.g. 'core/button').
	 * @param string            $handle       Style handle.
	 * @param string            $src          Relative path to the stylesheet.
	 * @param array<int,string> $dependencies Style dependencies.
	 *
	 * @return void
	 */
	public function enqueueBlockStyle(
		string $block_name,
		string $handle,
		string $src,
		array $dependencies = [],
	): void {
		$file = $this->path_resolver->resolve( $src );

		if ( ! is_file( $file ) || ! filesize( $file ) ) {
			return;
		}

		wp_enqueue_block_style(
			$block_name,
			[
				'handle' => $handle,
				'src'    => $this->url_resolver->resolve( $src ),
				'deps'   => apply_filters( "{$this->package}_style_dependencies_{$handle}", $dependencies ),
				'ver'    => filemtime( $file ),
				'path'   => $file,
			]
		);
	}
}
