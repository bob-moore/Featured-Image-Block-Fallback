<?php
/**
 * Featured image fallback transformer.
 *
 * @package Bmd\FeaturedImageBlockFallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 */

namespace Bmd\FeaturedImageBlockFallback\Transformers;

use Bmd\FeaturedImageBlockFallback\Module;

/**
 * Coordinates fallback image IDs while post-featured-image blocks render.
 */
class Fallback extends Module
{
	/**
	 * Map of post/fallback IDs.
	 *
	 * @var array<int, int>
	 */
	protected array $image_map = [];

	/**
	 * Filter the post thumbnail ID.
	 *
	 * @param int|false         $thumbnail_id Default thumbnail ID.
	 * @param int|\WP_Post|null $post         Post ID or object.
	 *
	 * @return int|false
	 */
	public function filterPostThumbnailId( int|false $thumbnail_id, int|\WP_Post|null $post ): int|false
	{
		if ( $thumbnail_id ) {
			return $thumbnail_id;
		}

		$post_id = $post instanceof \WP_Post ? $post->ID : intval( $post );

		return $this->image_map[ $post_id ] ?? $thumbnail_id;
	}

	/**
	 * Preprocess post featured image block context.
	 *
	 * @param array<string, mixed> $context Default context.
	 * @param array<string, mixed> $block   Parsed block.
	 *
	 * @return array<string, mixed>
	 */
	public function preProcessBlock( array $context, array $block ): array
	{
		if ( 'core/post-featured-image' !== ( $block['blockName'] ?? '' ) ) {
			return $context;
		}

		$post_id = intval( $context['postId'] ?? get_the_id() );

		if ( ! $post_id || (int) get_post_meta( $post_id, '_thumbnail_id', true ) ) {
			return $context;
		}

		$fallback_id = apply_filters(
			"{$this->package}_id",
			intval( $block['attrs']['featuredImageFallback']['id'] ?? 0 ),
			$block
		);
		$fallback_id = apply_filters( 'featured_image_block_fallback_id', $fallback_id, $block );

		if ( empty( $fallback_id ) || $this->shouldUseFirstImageFromPost( $post_id, $block ) ) {
			return $context;
		}

		$this->image_map[ $post_id ] = intval( $fallback_id );

		return $context;
	}

	/**
	 * Determine whether post content already supplies the first image fallback.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $block   Parsed block.
	 *
	 * @return bool
	 */
	protected function shouldUseFirstImageFromPost( int $post_id, array $block ): bool
	{
		if ( empty( $block['attrs']['useFirstImageFromPost'] ) ) {
			return false;
		}

		$content_post = get_post( $post_id );

		if ( ! $content_post instanceof \WP_Post ) {
			return true;
		}

		$processor = new \WP_HTML_Tag_Processor( $content_post->post_content );

		return $processor->next_tag( [ 'tag_name' => 'img' ] );
	}

	/**
	 * Clear the active fallback after the post featured image block has rendered.
	 *
	 * @param string               $block_content Rendered block markup.
	 * @param array<string, mixed> $block         Parsed block.
	 * @param \WP_Block|null       $instance      Block instance.
	 *
	 * @return string
	 */
	public function resetPostThumbnailFallback( string $block_content, array $block, ?\WP_Block $instance = null ): string
	{
		if ( 'core/post-featured-image' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		$post_id = intval( $instance->context['postId'] ?? get_the_id() );

		if ( $post_id ) {
			unset( $this->image_map[ $post_id ] );
		}

		return $block_content;
	}
}
