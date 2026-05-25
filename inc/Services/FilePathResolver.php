<?php
/**
 * File path resolver service.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback\Services;

use DI\Attribute\Inject;

/**
 * Resolves paths relative to the plugin root directory.
 */
class FilePathResolver
{
	/**
	 * Plugin root path without a trailing slash.
	 *
	 * @var string
	 */
	protected string $path = '';

	/**
	 * Constructor.
	 *
	 * @param string $path Root path of the plugin.
	 */
	#[Inject( [ 'path' => 'path' ] )]
	public function __construct( string $path )
	{
		$this->setDir( $path );
	}

	/**
	 * Set the root path.
	 *
	 * @param string $path Root path.
	 *
	 * @return void
	 */
	public function setDir( string $path ): void
	{
		$this->path = trim( untrailingslashit( $path ) );
	}

	/**
	 * Resolve a file path relative to the root path.
	 *
	 * @param string $append Path to append.
	 *
	 * @return string Complete file path.
	 */
	public function resolve( string $append = '' ): string
	{
		$append = trim( $append );
		return ! empty( $append )
			? trim( untrailingslashit( trailingslashit( $this->path ) . ltrim( $append, '/' ) ) )
			: $this->path;
	}
}
