<?php
/**
 * Tests for FeaturedImageBlockFallback.
 *
 * @package Bmd\FeaturedImageBlockFallback
 */

namespace Bmd\Tests;

use Bmd\FeaturedImageBlockFallback;
use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * @covers \Bmd\FeaturedImageBlockFallback
 */
class FeaturedImageBlockFallbackTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		WP_Mock::setUp();
	}

	protected function tearDown(): void
	{
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function mount_registers_expected_wordpress_hooks(): void
	{
		$plugin = new FeaturedImageBlockFallback(
			'https://example.test/wp-content/plugins/featured-image-block-fallback/',
			'/var/www/html/wp-content/plugins/featured-image-block-fallback/'
		);

		WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', [ $plugin, 'enqueueBlockAssets' ] );
		WP_Mock::expectFilterAdded( 'render_block_context', [ $plugin, 'preProcessBlock' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'post_thumbnail_id', [ $plugin, 'filterPostThumbnailId' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'render_block', [ $plugin, 'resetPostThumbnailFallback' ], 10, 3 );

		$plugin->mount();

		$this->addToAssertionCount( 4 );
	}

	/**
	 * @test
	 */
	public function build_path_and_url_resolve_files_inside_build_directory(): void
	{
		$plugin = new class(
			'https://example.test/plugin/',
			'/var/www/plugin/'
		) extends FeaturedImageBlockFallback {
			public function publicBuildPath( string $relative_path ): string
			{
				return $this->buildPath( $relative_path );
			}

			public function publicBuildUrl( string $relative_path ): string
			{
				return $this->buildUrl( $relative_path );
			}
		};

		WP_Mock::userFunction( 'apply_filters', [ 'return_arg' => 1 ] );

		$this->assertSame( '/var/www/plugin/build/index.js', $plugin->publicBuildPath( '/index.js' ) );
		$this->assertSame( 'https://example.test/plugin/build/index.css', $plugin->publicBuildUrl( 'index.css' ) );
	}

	/**
	 * @test
	 */
	public function build_path_returns_empty_string_when_filter_clears_path(): void
	{
		$plugin = new class(
			'https://example.test/plugin/',
			'/var/www/plugin/'
		) extends FeaturedImageBlockFallback {
			public function publicBuildPath( string $relative_path ): string
			{
				return $this->buildPath( $relative_path );
			}
		};

		WP_Mock::onFilter( 'featured_image_block_fallback_plugin_path' )
			->with( '/var/www/plugin/' )
			->reply( '' );

		$this->assertSame( '', $plugin->publicBuildPath( 'index.js' ) );
	}

	/**
	 * @test
	 */
	public function get_script_assets_reads_wordpress_asset_metadata(): void
	{
		$temp_root  = $this->createTemporaryPluginRoot();
		$asset_file = $temp_root . 'build/index.asset.php';

		file_put_contents(
			$asset_file,
			"<?php\nreturn [ 'dependencies' => [ 'wp-blocks', 'wp-element' ], 'version' => 'abc123' ];\n"
		);

		$plugin = new class( 'https://example.test/plugin/', $temp_root ) extends FeaturedImageBlockFallback {
			/** @return array{dependencies: array<int, string>, version: string|null} */
			public function publicGetScriptAssets(): array
			{
				return $this->getScriptAssets();
			}
		};

		WP_Mock::userFunction( 'apply_filters', [ 'return_arg' => 1 ] );

		$this->assertSame(
			[
				'dependencies' => [ 'wp-blocks', 'wp-element' ],
				'version'      => 'abc123',
			],
			$plugin->publicGetScriptAssets()
		);
	}

	/**
	 * @test
	 */
	public function get_script_assets_returns_empty_defaults_when_no_asset_file_exists(): void
	{
		$temp_root = $this->createTemporaryPluginRoot();

		$plugin = new class( 'https://example.test/plugin/', $temp_root ) extends FeaturedImageBlockFallback {
			/** @return array{dependencies: array<int, string>, version: string|null} */
			public function publicGetScriptAssets(): array
			{
				return $this->getScriptAssets();
			}
		};

		WP_Mock::userFunction( 'apply_filters', [ 'return_arg' => 1 ] );

		$this->assertSame(
			[
				'dependencies' => [],
				'version'      => null,
			],
			$plugin->publicGetScriptAssets()
		);
	}

	/**
	 * @test
	 */
	public function enqueue_block_assets_registers_script_and_style_when_build_files_exist(): void
	{
		$temp_root  = $this->createTemporaryPluginRoot();
		$asset_file = $temp_root . 'build/index.asset.php';
		$style_file = $temp_root . 'build/index.css';

		file_put_contents(
			$asset_file,
			"<?php\nreturn [ 'dependencies' => [ 'wp-blocks' ], 'version' => 'v1.0' ];\n"
		);
		file_put_contents( $style_file, '/* styles */' );

		$plugin = new FeaturedImageBlockFallback( 'https://example.test/plugin/', $temp_root );

		WP_Mock::userFunction( 'apply_filters', [ 'return_arg' => 1 ] );
		WP_Mock::userFunction(
			'wp_enqueue_style',
			[
				'times' => 1,
				'args'  => [
					'featured-image-block-fallback',
					'https://example.test/plugin/build/index.css',
					[],
					'v1.0',
					'all',
				],
			]
		);
		WP_Mock::userFunction(
			'wp_enqueue_script',
			[
				'times' => 1,
				'args'  => [
					'featured-image-block-fallback',
					'https://example.test/plugin/build/index.js',
					[ 'wp-blocks' ],
					'v1.0',
					true,
				],
			]
		);

		$plugin->enqueueBlockAssets();

		$this->addToAssertionCount( 2 );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_returns_existing_thumbnail_unchanged(): void
	{
		$plugin = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );

		$this->assertSame( 42, $plugin->filterPostThumbnailId( 42, 5 ) );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_returns_fallback_from_image_map(): void
	{
		$plugin = new class( 'https://example.test/plugin/', '/var/www/plugin/' ) extends FeaturedImageBlockFallback {
			public function seedImageMap( array $map ): void
			{
				$this->image_map = $map;
			}
		};

		$plugin->seedImageMap( [ 5 => 99 ] );

		$this->assertSame( 99, $plugin->filterPostThumbnailId( false, 5 ) );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_returns_false_when_no_fallback_is_mapped(): void
	{
		$plugin = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );

		$this->assertFalse( $plugin->filterPostThumbnailId( false, 5 ) );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_accepts_wp_post_object(): void
	{
		$plugin = new class( 'https://example.test/plugin/', '/var/www/plugin/' ) extends FeaturedImageBlockFallback {
			public function seedImageMap( array $map ): void
			{
				$this->image_map = $map;
			}
		};

		$post     = new \WP_Post( [ 'ID' => 7 ] );
		$plugin->seedImageMap( [ 7 => 55 ] );

		$this->assertSame( 55, $plugin->filterPostThumbnailId( false, $post ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_ignores_non_featured_image_blocks(): void
	{
		$plugin  = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );
		$context = [ 'postId' => 5 ];
		$block   = [ 'blockName' => 'core/paragraph' ];

		$this->assertSame( $context, $plugin->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_ignores_posts_that_already_have_a_thumbnail(): void
	{
		$plugin  = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );
		$context = [ 'postId' => 5 ];
		$block   = [ 'blockName' => 'core/post-featured-image', 'attrs' => [] ];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 42,
		] );

		$this->assertSame( $context, $plugin->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_ignores_when_no_fallback_id_is_configured(): void
	{
		$plugin  = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );
		$context = [ 'postId' => 5 ];
		$block   = [ 'blockName' => 'core/post-featured-image', 'attrs' => [] ];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 0,
		] );
		WP_Mock::userFunction( 'apply_filters', [ 'return' => 0 ] );

		$this->assertSame( $context, $plugin->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_maps_fallback_image_id_for_the_post(): void
	{
		$plugin = new class( 'https://example.test/plugin/', '/var/www/plugin/' ) extends FeaturedImageBlockFallback {
			public function getImageMap(): array
			{
				return $this->image_map;
			}
		};

		$context = [ 'postId' => 5 ];
		$block   = [
			'blockName' => 'core/post-featured-image',
			'attrs'     => [ 'featuredImageFallback' => [ 'id' => 99 ] ],
		];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 0,
		] );
		WP_Mock::userFunction( 'apply_filters', [ 'return' => 99 ] );

		$plugin->preProcessBlock( $context, $block );

		$this->assertSame( [ 5 => 99 ], $plugin->getImageMap() );
	}

	/**
	 * @test
	 */
	public function pre_process_block_skips_fallback_when_post_content_has_an_image(): void
	{
		$plugin  = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );
		$context = [ 'postId' => 5 ];
		$block   = [
			'blockName' => 'core/post-featured-image',
			'attrs'     => [
				'featuredImageFallback' => [ 'id' => 99 ],
				'useFirstImageFromPost' => true,
			],
		];

		$post               = new \WP_Post();
		$post->ID           = 5;
		$post->post_content = '<img src="photo.jpg" alt="A photo" />';

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 0,
		] );
		WP_Mock::userFunction( 'apply_filters', [ 'return' => 99 ] );
		WP_Mock::userFunction( 'get_post', [
			'args'   => [ 5 ],
			'return' => $post,
		] );

		$this->assertSame( $context, $plugin->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_maps_fallback_when_use_first_image_is_true_but_content_has_no_image(): void
	{
		$plugin = new class( 'https://example.test/plugin/', '/var/www/plugin/' ) extends FeaturedImageBlockFallback {
			public function getImageMap(): array
			{
				return $this->image_map;
			}
		};

		$context = [ 'postId' => 5 ];
		$block   = [
			'blockName' => 'core/post-featured-image',
			'attrs'     => [
				'featuredImageFallback' => [ 'id' => 99 ],
				'useFirstImageFromPost' => true,
			],
		];

		$post               = new \WP_Post();
		$post->ID           = 5;
		$post->post_content = '<p>Only text, no image here.</p>';

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 0,
		] );
		WP_Mock::userFunction( 'apply_filters', [ 'return' => 99 ] );
		WP_Mock::userFunction( 'get_post', [
			'args'   => [ 5 ],
			'return' => $post,
		] );

		$plugin->preProcessBlock( $context, $block );

		$this->assertSame( [ 5 => 99 ], $plugin->getImageMap() );
	}

	/**
	 * @test
	 */
	public function pre_process_block_skips_fallback_when_get_post_returns_non_post(): void
	{
		$plugin  = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );
		$context = [ 'postId' => 5 ];
		$block   = [
			'blockName' => 'core/post-featured-image',
			'attrs'     => [
				'featuredImageFallback' => [ 'id' => 99 ],
				'useFirstImageFromPost' => true,
			],
		];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 0,
		] );
		WP_Mock::userFunction( 'apply_filters', [ 'return' => 99 ] );
		WP_Mock::userFunction( 'get_post', [
			'args'   => [ 5 ],
			'return' => null,
		] );

		$this->assertSame( $context, $plugin->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function reset_post_thumbnail_fallback_ignores_non_featured_image_blocks(): void
	{
		$plugin        = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );
		$block_content = '<div>Some block</div>';
		$block         = [ 'blockName' => 'core/paragraph' ];

		$this->assertSame( $block_content, $plugin->resetPostThumbnailFallback( $block_content, $block, null ) );
	}

	/**
	 * @test
	 */
	public function reset_post_thumbnail_fallback_clears_image_map_entry_for_the_post(): void
	{
		$plugin = new class( 'https://example.test/plugin/', '/var/www/plugin/' ) extends FeaturedImageBlockFallback {
			public function seedImageMap( array $map ): void
			{
				$this->image_map = $map;
			}

			public function getImageMap(): array
			{
				return $this->image_map;
			}
		};

		$plugin->seedImageMap( [ 5 => 99, 7 => 44 ] );

		$instance          = new \WP_Block();
		$instance->context = [ 'postId' => 5 ];

		$block_content = '<div class="wp-block-post-featured-image"></div>';
		$block         = [ 'blockName' => 'core/post-featured-image' ];

		$result = $plugin->resetPostThumbnailFallback( $block_content, $block, $instance );

		$this->assertSame( $block_content, $result );
		$this->assertSame( [ 7 => 44 ], $plugin->getImageMap() );
	}

	/**
	 * @test
	 */
	public function reset_post_thumbnail_fallback_returns_content_unchanged(): void
	{
		$plugin        = new FeaturedImageBlockFallback( 'https://example.test/plugin/', '/var/www/plugin/' );
		$block_content = '<div class="wp-block-post-featured-image"><img src="fallback.jpg"></div>';
		$block         = [ 'blockName' => 'core/post-featured-image' ];

		$instance          = new \WP_Block();
		$instance->context = [ 'postId' => 5 ];

		$this->assertSame( $block_content, $plugin->resetPostThumbnailFallback( $block_content, $block, $instance ) );
	}

	private function createTemporaryPluginRoot(): string
	{
		$root = trailingslashit( sys_get_temp_dir() ) . 'fibf-tests-' . uniqid( '', true ) . '/';
		mkdir( $root . 'build', 0777, true );
		return $root;
	}
}
