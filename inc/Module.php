<?php
/**
 * Base module class.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback;

use DI\Attribute\Inject;

/**
 * Abstract base for all injectable plugin classes.
 *
 * Provides package slug injection from the DI container so that every
 * module can use it in filter and action names without receiving it
 * manually through a constructor chain.
 */
abstract class Module
{
	/**
	 * Package slug for this module.
	 *
	 * @var string
	 */
	protected string $package = '';

	/**
	 * Constructor.
	 *
	 * @param string $package Package slug for this module.
	 */
	#[Inject( [ 'package' => 'package' ] )]
	public function setPackage( string $package ): void
	{
		$this->package = sanitize_key( str_replace( '-', '_', trim( $package ) ) );
	}
}
