<?php
/**
 * Hook registrar.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback;

use DI\Attribute\Inject;

/**
 * Registers all WordPress hooks for the plugin.
 */
class Controller extends Module
{
	/**
	 * Register WordPress action hooks.
	 *
	 * @param Providers\Assets $assets Asset provider.
	 *
	 * @return void
	 */
	#[Inject]
	public function registerActions( Providers\Assets $assets ): void
	{
		add_action( 'enqueue_block_editor_assets', [ $assets, 'enqueueEditorAssets' ] );
	}

	/**
	 * Register WordPress filter hooks.
	 *
	 * @param Transformers\Fallback $fallback Featured image fallback transformer.
	 *
	 * @return void
	 */
	#[Inject]
	public function registerFilters( Transformers\Fallback $fallback ): void
	{
		add_filter( 'render_block_context', [ $fallback, 'preProcessBlock' ], 10, 2 );
		add_filter( 'post_thumbnail_id', [ $fallback, 'filterPostThumbnailId' ], 10, 2 );
		add_filter( 'render_block', [ $fallback, 'resetPostThumbnailFallback' ], 10, 3 );
	}
}
