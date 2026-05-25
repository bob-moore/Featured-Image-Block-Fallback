<?php
/**
 * URL resolver service.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback\Services;

use DI\Attribute\Inject;

/**
 * Resolves paths relative to the plugin root URL.
 */
class UrlResolver
{
	/**
	 * Plugin root URL without a trailing slash.
	 *
	 * @var string
	 */
	protected string $url = '';

	/**
	 * Constructor.
	 *
	 * @param string $url Root URL of the plugin.
	 */
	#[Inject( [ 'url' => 'url' ] )]
	public function __construct( string $url )
	{
		$this->setUrl( $url );
	}

	/**
	 * Set the root URL.
	 *
	 * @param string $url Root URL.
	 *
	 * @return void
	 */
	public function setUrl( string $url ): void
	{
		$this->url = untrailingslashit( esc_url_raw( $url ) );
	}

	/**
	 * Resolve a URL relative to the root URL.
	 *
	 * @param string $append Path to append.
	 *
	 * @return string Complete URL.
	 */
	public function resolve( string $append = '' ): string
	{
		return ! empty( $append )
			? esc_url_raw( trailingslashit( $this->url ) . ltrim( $append, '/' ) )
			: esc_url_raw( $this->url );
	}
}
