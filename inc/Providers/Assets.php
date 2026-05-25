<?php
/**
 * Asset provider.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback\Providers;

use Bmd\FeaturedImageBlockFallback\Module;
use Bmd\FeaturedImageBlockFallback\Services;

/**
 * Enqueues editor assets.
 */
class Assets extends Module
{
	protected const EDITOR_SCRIPT_HANDLE = 'featured-image-block-fallback';
	protected const EDITOR_STYLE_HANDLE  = 'featured-image-block-fallback';

	/**
	 * Constructor.
	 *
	 * @param Services\ScriptLoader $script_loader Script loader.
	 * @param Services\StyleLoader  $style_loader  Style loader.
	 */
	public function __construct(
		protected Services\ScriptLoader $script_loader,
		protected Services\StyleLoader $style_loader,
	) {
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueueEditorAssets(): void
	{
		$this->script_loader->enqueue(
			handle: self::EDITOR_SCRIPT_HANDLE,
			src: 'build/index.js'
		);

		$this->style_loader->enqueue(
			handle: self::EDITOR_STYLE_HANDLE,
			src: 'build/index.css'
		);
	}
}
