<?php
/**
 * Interface for basic plugin structure.
 *
 * PHP Version 8.2
 *
 * @package    Bmd\FeaturedImageBlockFallback
 * @author     Bob Moore <bob@bobmoore.dev>
 * @license    GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link       https://www.bobmoore.dev
 * @since      1.0.0
 */

namespace Bmd;

interface BasicPlugin
{
	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function mount(): void;

	/**
	 * Set the plugin URL.
	 *
	 * @param string $url URL to set.
	 *
	 * @return void
	 */
	public function setUrl( string $url ): void;

	/**
	 * Set the plugin path.
	 *
	 * @param string $path Path to set.
	 *
	 * @return void
	 */
	public function setPath( string $path ): void;
}
