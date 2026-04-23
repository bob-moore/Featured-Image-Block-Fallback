<?php
/**
 * Main plugin file
 *
 * PHP Version 8.2
 *
 * @package    Bmd\FeaturedImageBlockFallback
 * @author     Bob Moore <bob@bobmoore.dev>
 * @license    GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link       https://github.com/bob-moore/Featured-Image-Block-Fallback
 * @since      0.1.0
 */

namespace Bmd;

/**
 * Featured image block fallback class definition
 */
class FeaturedImageBlockFallback implements BasicPlugin
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
		$this->setUrl( ! empty( $url ) ? esc_url_raw( $url ) : plugin_dir_url( __DIR__ ) );
		$this->setPath( ! empty( $path ) ? esc_html( $path ) : plugin_dir_path( __DIR__ ) );
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
		$this->url = trailingslashit( $url );
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
		$this->path = trailingslashit( $path );
	}

	/**
	 * Build an absolute path inside the package build directory.
	 *
	 * @param string $relative_path Relative file path inside build.
	 *
	 * @return string
	 */
	protected function buildPath( string $relative_path ): string
	{
		$path = apply_filters( 'featured_image_block_fallback_plugin_path', $this->path );

		if ( '' === $path ) {
			return '';
		}

		return wp_normalize_path( $path . 'build/' . ltrim( $relative_path, '/' ) );
	}

	/**
	 * Resolve a build file path into a public URL.
	 *
	 * @param string $relative_path Relative file path inside build.
	 *
	 * @return string
	 */
	protected function buildUrl( string $relative_path ): string
	{
		$url = apply_filters( 'featured_image_block_fallback_plugin_url', $this->url );

		if ( '' === $url ) {
			return '';
		}

		return $url . 'build/' . ltrim( $relative_path, '/' );
	}

	/**
	 * Load the wp-scripts asset manifest, checking both naming conventions.
	 *
	 * @return array{dependencies: string[], version: string|null}
	 */
	protected function getScriptAssets(): array
	{
		$asset_candidates = [
			$this->buildPath( 'index.asset.php' ),
			$this->buildPath( 'index.assets.php' ),
		];

		foreach ( $asset_candidates as $asset_file ) {
			if ( ! is_file( $asset_file ) ) {
				continue;
			}

			$asset = include $asset_file;

			if ( ! is_array( $asset ) ) {
				continue;
			}

			$dependencies = $asset['dependencies'] ?? [];
			$version      = $asset['version'] ?? null;

			return [
				'dependencies' => is_array( $dependencies ) ? $dependencies : [],
				'version'      => is_string( $version ) ? $version : null,
			];
		}

		return [
			'dependencies' => [],
			'version'      => null,
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
		$assets     = $this->getScriptAssets();
		$style_file = $this->buildPath( 'index.css' );

		if ( ! empty( $style_file ) && is_file( $style_file ) ) {
			// Keep style version in sync with script build version when available.
			$version = $assets['version'] ?? (string) filemtime( $style_file );

			wp_enqueue_style(
				'featured-image-block-fallback',
				$this->buildUrl( 'index.css' ),
				[],
				$version,
				'all'
			);
		}

		$script_url = $this->buildUrl( 'index.js' );

		if ( ! empty( $script_url ) ) {
			wp_enqueue_script(
				'featured-image-block-fallback',
				$script_url,
				$assets['dependencies'],
				$assets['version'],
				true
			);
		}
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
