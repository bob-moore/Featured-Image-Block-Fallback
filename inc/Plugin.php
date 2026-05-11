<?php
/**
 * Main plugin service.
 *
 * PHP Version 8.2
 *
 * @package    Bmd\FeaturedImageBlockFallback
 * @author     Bob Moore <bob@bobmoore.dev>
 * @license    GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link       https://github.com/bob-moore/Featured-Image-Block-Fallback
 * @since      0.1.0
 */

namespace Bmd\FeaturedImageBlockFallback;

/**
 * Featured image block fallback service class.
 */
class Plugin
{
	/**
	 * URL of this plugin/package
	 *
	 * Used to enqueue block editor assets.
	 *
	 * @var string
	 */
	protected string $url;
	/**
	 * Path of the plugin/package
	 *
	 * Used to locate block editor assets.
	 *
	 * @var string
	 */
	protected string $path;
	/**
	 * Map of post/fallback ids
	 *
	 * @var array<int, int>
	 */
	protected array $image_map = [];

	/**
	 * Initialize the plugin.
	 *
	 * Sets the URL and path for this package.
	 *
	 * @param string $url URL to the plugin directory.
	 * @param string $path Absolute path to the plugin directory.
	 */
	public function __construct(
		string $url = '',
		string $path = ''
	) {
		$this->setUrl( ! empty( $url ) ? $url : Utilities::getUrl() );
		$this->setPath( ! empty( $path ) ? $path : Utilities::getPath() );
	}

	/**
	 * Setter for the URL property.
	 *
	 * @param string $url string URL to set.
	 *
	 * @return void
	 */
	public function setUrl( string $url ): void
	{
		$filtered  = (string) apply_filters( 'featured_image_block_fallback_plugin_url', $url );
		$this->url = '' !== $filtered ? trailingslashit( esc_url_raw( $filtered ) ) : '';
	}

	/**
	 * Setter for the path property.
	 *
	 * @param string $path string path to set.
	 *
	 * @return void
	 */
	public function setPath( string $path ): void
	{
		$filtered   = (string) apply_filters( 'featured_image_block_fallback_plugin_path', $path );
		$this->path = '' !== $filtered ? trailingslashit( $filtered ) : '';
	}

	/**
	 * Resolve script dependency metadata from a WordPress build asset file.
	 *
	 * @param string $key Build asset key without the `.asset.php` suffix.
	 *
	 * @return array{dependencies: array<int, string>, version: string|null}
	 */
	protected function getAssetData( string $key ): array
	{
		$asset_file = $this->path . "build/{$key}.asset.php";

		if ( ! is_file( $asset_file ) ) {
			return [
				'dependencies' => [],
				'version'      => null,
			];
		}

		$asset = include $asset_file;

		if ( ! is_array( $asset ) ) {
			return [
				'dependencies' => [],
				'version'      => null,
			];
		}

		$dependencies = $asset['dependencies'] ?? [];
		$version      = $asset['version'] ?? null;

		return [
			'dependencies' => is_array( $dependencies ) ? $dependencies : [],
			'version'      => is_string( $version ) ? $version : null,
		];
	}

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	public function mount(): void
	{
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueBlockAssets' ] );
		add_filter( 'render_block_context', [ $this, 'preProcessBlock' ], 10, 2 );
		add_filter( 'post_thumbnail_id', [ $this, 'filterPostThumbnailId' ], 10, 2 );
		add_filter( 'render_block', [ $this, 'resetPostThumbnailFallback' ], 10, 3 );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueueBlockAssets(): void
	{
		$script_file = $this->path . 'build/index.js';

		if ( ! is_file( $script_file ) ) {
			return;
		}

		$assets = $this->getAssetData( 'index' );

		wp_enqueue_script(
			'featured-image-block-fallback',
			$this->url . 'build/index.js',
			$assets['dependencies'],
			$assets['version'] ?? (string) filemtime( $script_file ),
			true
		);

		$style_file = $this->path . 'build/index.css';

		if ( ! is_file( $style_file ) ) {
			return;
		}

		wp_enqueue_style(
			'featured-image-block-fallback',
			$this->url . 'build/index.css',
			[],
			$assets['version'] ?? (string) filemtime( $style_file ),
			'all'
		);
	}

	/**
	 * Filters the post thumbnail ID.
	 *
	 * @param integer|false     $thumbnail_id the default thumbnail ID.
	 * @param int|\WP_Post|null $post The post ID or post object to get the thumbnail for.
	 *
	 * @return integer|false
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
	 * Preprocess the block context.
	 *
	 * Used to check the conditions, and conditionally set the fallback image.
	 *
	 * @param array<string, mixed> $context Default context.
	 * @param array<string, mixed> $block   The block attributes.
	 *
	 * @return array<string, mixed> The (un)modified block context.
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
			'featured_image_block_fallback_id',
			intval( $block['attrs']['featuredImageFallback']['id'] ?? 0 ),
			$block
		);

		if ( empty( $fallback_id ) ) {
			return $context;
		}

		if ( $block['attrs']['useFirstImageFromPost'] ?? false ) {
			$content_post = get_post( $post_id );
			if ( ! $content_post instanceof \WP_Post ) {
				return $context;
			}

			$content      = $content_post->post_content;
			$processor    = new \WP_HTML_Tag_Processor( $content );
			/**
			 * If it has an image in the content, we don't need to use the manual fallback
			 */
			if ( $processor->next_tag( [ 'tag_name' => 'img' ] ) ) {
				return $context;
			}
		}

		$this->image_map[ $post_id ] = intval( $fallback_id );

		return $context;
	}

	/**
	 * Clear the active fallback after the post featured image block has rendered.
	 *
	 * @param string               $block_content The block content.
	 * @param array<string, mixed> $block         The block attributes.
	 * @param \WP_Block|null       $instance      Block instance.
	 *
	 * @return string The unmodified block content.
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
