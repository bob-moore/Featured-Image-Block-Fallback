<?php
/**
 * Main plugin file
 *
 * PHP Version 8.2
 *
 * @package featured-image-block-fallback
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/Featured-Image-Block-Fallback
 * @since   0.1.0
 */

namespace Bmd;

/**
 * Featured image block fallback class definition
 */
class FeaturedImageBlockFallback {
	/**
	 * URI of this plugin/package
	 *
	 * Used to enqueue block editor assets.
	 *
	 * @var string
	 */
	protected string $uri;
	/**
	 * Path of the plugin/package
	 *
	 * Used to locate block editor assets.
	 *
	 * @var string
	 */
	protected string $path;
	/**
	 * ID of current fallback image in the queue.
	 *
	 * @var integer|null
	 */
	protected ?int $current_fallback_id = null;
	/**
	 * Map of post/fallback ids
	 *
	 * @var array<int, int>
	 */
	protected array $image_map = [];

	/**
	 * Initialize the plugin.
	 *
	 * Sets the URI and path of the plugin if not passed as arguments.
	 *
	 * @param string $uri Optional path to the plugin URI.
	 * @param string $path Optional path to the plugin directory.
	 */
	public function __construct(
		string $uri = '',
		string $path = ''
	) {
		$this->setUri( ! empty( $uri ) ? $uri : plugin_dir_url( __DIR__ ) );
		$this->setPath( ! empty( $path ) ? $path : plugin_dir_path( __DIR__ ) );
	}
	/**
	 * Setter for the URI property.
	 *
	 * @param string $uri string URI to set.
	 *
	 * @return self
	 */
	public function setUri( string $uri ): self
	{
		$this->uri = trailingslashit( $uri );

		return $this;
	}

	/**
	 * Setter for the path property.
	 *
	 * @param string $path string path to set.
	 *
	 * @return self
	 */
	public function setPath( string $path ): self
	{
		$this->path = trailingslashit( $path );

		return $this;
	}
	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function mount(): void
	{
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueBlockAssets' ] );
		add_filter( 'pre_render_block', [ $this, 'preProcessBlock' ], 10, 2 );
		add_filter( 'post_thumbnail_id', [ $this, 'filterPostThumbnailId' ], 10, 2 );
	}
	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueueBlockAssets(): void
	{
		$build_data = include $this->path . 'build/index.asset.php';

		wp_enqueue_style(
			'featured-image-block-fallback',
			$this->uri . 'build/index.css',
			[],
			$build_data['version'],
			'all'
		);

		wp_enqueue_script(
			'featured-image-block-fallback',
			$this->uri . 'build/index.js',
			$build_data['dependencies'],
			$build_data['version'],
			true
		);
	}
	/**
	 * Filters the post thumbnail ID.
	 *
	 * @param integer|false     $thumbnail_id the default thumbnail ID.
	 * @param int|\WP_Post|null $post The post ID or post object to get the thumbnail for.
	 *
	 * @return integer
	 */
	public function filterPostThumbnailId( int|false $thumbnail_id, int|\WP_Post|null $post ): int|false
	{
		if ( $thumbnail_id ) {
			return $thumbnail_id;
		}

		$post_id = $post instanceof \WP_Post ? $post->ID : intval( $post );
		;

		return $this->image_map[ $post_id ] ?? $thumbnail_id;
	}
	/**
	 * Preprocess the block content.
	 *
	 * Used to check the conditions, and conditionally add the necessary filter.
	 *
	 * @param string|null $block_content The block content.
	 * @param array{
	 *   blockName?: string,
	 *   attrs?: array{
	 *     featuredImageFallback?: array{id?: int|string},
	 *     useFirstImageFromPost?: bool
	 *   }
	 * } $block The block attributes.
	 *
	 * @return string|null The (un)modified block content.
	 */
	public function preProcessBlock( string|null $block_content, array $block ): ?string
	{
		if (
			'core/post-featured-image' !== ( $block['blockName'] ?? '' )
			|| has_post_thumbnail( get_the_id() )
		) {
			return $block_content;
		}

		$fallback_id = apply_filters(
			'featured_image_block_fallback_id',
			intval( $block['attrs']['featuredImageFallback']['id'] ?? 0 ),
			$block
		);

		if ( empty( $fallback_id ) ) {
			return $block_content;
		}

		if ( $block['attrs']['useFirstImageFromPost'] ?? false ) {
			$content_post = get_post( get_the_id() );
			$content      = $content_post->post_content;
			$processor    = new \WP_HTML_Tag_Processor( $content );
			/**
			 * If it has an image in the content, we don't need to use the manual fallback
			 */
			if ( $processor->next_tag( [ 'tag_name' => 'img' ] ) ) {
				return $block_content;
			}
		}

		$this->image_map[ get_the_id() ] = intval( $fallback_id );

		return $block_content;
	}
}
