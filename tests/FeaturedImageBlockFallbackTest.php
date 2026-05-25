<?php
/**
 * Tests for Featured Image Block Fallback.
 *
 * @package Bmd\FeaturedImageBlockFallback
 */

namespace Bmd\FeaturedImageBlockFallback\Tests;

use Bmd\FeaturedImageBlockFallback\Controller;
use Bmd\FeaturedImageBlockFallback\Providers\Assets;
use Bmd\FeaturedImageBlockFallback\Services\FilePathResolver;
use Bmd\FeaturedImageBlockFallback\Services\ScriptLoader;
use Bmd\FeaturedImageBlockFallback\Services\StyleLoader;
use Bmd\FeaturedImageBlockFallback\Services\UrlResolver;
use Bmd\FeaturedImageBlockFallback\Transformers\Fallback;
use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * @covers \Bmd\FeaturedImageBlockFallback\Controller
 * @covers \Bmd\FeaturedImageBlockFallback\Providers\Assets
 * @covers \Bmd\FeaturedImageBlockFallback\Services\AssetLoader
 * @covers \Bmd\FeaturedImageBlockFallback\Services\ScriptLoader
 * @covers \Bmd\FeaturedImageBlockFallback\Services\StyleLoader
 * @covers \Bmd\FeaturedImageBlockFallback\Transformers\Fallback
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
	public function controller_registers_expected_wordpress_hooks(): void
	{
		$assets     = $this->createMock( Assets::class );
		$fallback   = $this->createMock( Fallback::class );
		$controller = new Controller();

		$controller->setPackage( 'featured_image_block_fallback' );

		WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', [ $assets, 'enqueueEditorAssets' ] );
		WP_Mock::expectFilterAdded( 'render_block_context', [ $fallback, 'preProcessBlock' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'post_thumbnail_id', [ $fallback, 'filterPostThumbnailId' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'render_block', [ $fallback, 'resetPostThumbnailFallback' ], 10, 3 );

		$controller->registerActions( $assets );
		$controller->registerFilters( $fallback );

		$this->addToAssertionCount( 4 );
	}

	/**
	 * @test
	 */
	public function asset_loader_reads_wordpress_asset_metadata(): void
	{
		$temp_root  = $this->createTemporaryPluginRoot();
		$asset_file = $temp_root . 'build/index.asset.php';

		file_put_contents(
			$asset_file,
			"<?php\nreturn [ 'dependencies' => [ 'wp-blocks', 'wp-element' ], 'version' => 'abc123' ];\n"
		);

		$script_loader = $this->createScriptLoader( 'https://example.test/plugin/', $temp_root );

		$this->assertSame(
			[
				'dependencies' => [ 'wp-blocks', 'wp-element' ],
				'version'      => 'abc123',
			],
			$script_loader->getAssetData( 'index' )
		);
	}

	/**
	 * @test
	 */
	public function asset_loader_returns_empty_defaults_when_no_asset_file_exists(): void
	{
		$script_loader = $this->createScriptLoader(
			'https://example.test/plugin/',
			$this->createTemporaryPluginRoot()
		);

		$this->assertSame(
			[
				'dependencies' => [],
				'version'      => null,
			],
			$script_loader->getAssetData( 'index' )
		);
	}

	/**
	 * @test
	 */
	public function assets_provider_registers_and_enqueues_editor_assets(): void
	{
		$temp_root   = $this->createTemporaryPluginRoot();
		$asset_file  = $temp_root . 'build/index.asset.php';
		$script_file = $temp_root . 'build/index.js';
		$style_file  = $temp_root . 'build/index.css';

		file_put_contents(
			$asset_file,
			"<?php\nreturn [ 'dependencies' => [ 'wp-blocks' ], 'version' => 'v1.0' ];\n"
		);
		file_put_contents( $script_file, '/* script */' );
		file_put_contents( $style_file, '/* styles */' );

		$assets = $this->createAssetsProvider( 'https://example.test/plugin/', $temp_root );

		WP_Mock::userFunction(
			'apply_filters',
			[
				'return_arg' => 2,
			]
		);
		WP_Mock::userFunction(
			'wp_register_script',
			[
				'times'  => 1,
				'return' => static fn ( ...$args ): bool => true,
			]
		);
		WP_Mock::userFunction( 'wp_enqueue_script', [ 'times' => 1, 'return' => static fn ( ...$args ) => null ] );
		WP_Mock::userFunction(
			'wp_register_style',
			[
				'times'  => 1,
				'return' => static fn ( ...$args ): bool => true,
			]
		);
		WP_Mock::userFunction( 'wp_enqueue_style', [ 'times' => 1, 'return' => static fn ( ...$args ) => null ] );

		$assets->enqueueEditorAssets();

		$this->addToAssertionCount( 4 );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_returns_existing_thumbnail_unchanged(): void
	{
		$fallback = $this->createFallback();

		$this->assertSame( 42, $fallback->filterPostThumbnailId( 42, 5 ) );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_returns_fallback_from_image_map(): void
	{
		$fallback = $this->createInspectableFallback();
		$fallback->seedImageMap( [ 5 => 99 ] );

		$this->assertSame( 99, $fallback->filterPostThumbnailId( false, 5 ) );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_returns_false_when_no_fallback_is_mapped(): void
	{
		$fallback = $this->createFallback();

		$this->assertFalse( $fallback->filterPostThumbnailId( false, 5 ) );
	}

	/**
	 * @test
	 */
	public function filter_post_thumbnail_id_accepts_wp_post_object(): void
	{
		$fallback = $this->createInspectableFallback();
		$post     = new \WP_Post( [ 'ID' => 7 ] );

		$fallback->seedImageMap( [ 7 => 55 ] );

		$this->assertSame( 55, $fallback->filterPostThumbnailId( false, $post ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_ignores_non_featured_image_blocks(): void
	{
		$fallback = $this->createFallback();
		$context  = [ 'postId' => 5 ];
		$block    = [ 'blockName' => 'core/paragraph' ];

		$this->assertSame( $context, $fallback->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_ignores_posts_that_already_have_a_thumbnail(): void
	{
		$fallback = $this->createFallback();
		$context  = [ 'postId' => 5 ];
		$block    = [ 'blockName' => 'core/post-featured-image', 'attrs' => [] ];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 42,
		] );

		$this->assertSame( $context, $fallback->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_ignores_when_no_fallback_id_is_configured(): void
	{
		$fallback = $this->createFallback();
		$context  = [ 'postId' => 5 ];
		$block    = [ 'blockName' => 'core/post-featured-image', 'attrs' => [] ];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 0,
		] );
		WP_Mock::userFunction( 'apply_filters', [ 'return' => 0 ] );

		$this->assertSame( $context, $fallback->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_maps_fallback_image_id_for_the_post(): void
	{
		$fallback = $this->createInspectableFallback();
		$context  = [ 'postId' => 5 ];
		$block    = [
			'blockName' => 'core/post-featured-image',
			'attrs'     => [ 'featuredImageFallback' => [ 'id' => 99 ] ],
		];

		WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ 5, '_thumbnail_id', true ],
			'return' => 0,
		] );
		WP_Mock::userFunction( 'apply_filters', [ 'return' => 99 ] );

		$fallback->preProcessBlock( $context, $block );

		$this->assertSame( [ 5 => 99 ], $fallback->getImageMap() );
	}

	/**
	 * @test
	 */
	public function pre_process_block_skips_fallback_when_post_content_has_an_image(): void
	{
		$fallback = $this->createFallback();
		$context  = [ 'postId' => 5 ];
		$block    = [
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

		$this->assertSame( $context, $fallback->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function pre_process_block_maps_fallback_when_use_first_image_is_true_but_content_has_no_image(): void
	{
		$fallback = $this->createInspectableFallback();
		$context  = [ 'postId' => 5 ];
		$block    = [
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

		$fallback->preProcessBlock( $context, $block );

		$this->assertSame( [ 5 => 99 ], $fallback->getImageMap() );
	}

	/**
	 * @test
	 */
	public function pre_process_block_skips_fallback_when_get_post_returns_non_post(): void
	{
		$fallback = $this->createFallback();
		$context  = [ 'postId' => 5 ];
		$block    = [
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

		$this->assertSame( $context, $fallback->preProcessBlock( $context, $block ) );
	}

	/**
	 * @test
	 */
	public function reset_post_thumbnail_fallback_ignores_non_featured_image_blocks(): void
	{
		$fallback      = $this->createFallback();
		$block_content = '<div>Some block</div>';
		$block         = [ 'blockName' => 'core/paragraph' ];

		$this->assertSame( $block_content, $fallback->resetPostThumbnailFallback( $block_content, $block, null ) );
	}

	/**
	 * @test
	 */
	public function reset_post_thumbnail_fallback_clears_image_map_entry_for_the_post(): void
	{
		$fallback = $this->createInspectableFallback();
		$fallback->seedImageMap( [ 5 => 99, 7 => 44 ] );

		$instance          = new \WP_Block();
		$instance->context = [ 'postId' => 5 ];

		$block_content = '<div class="wp-block-post-featured-image"></div>';
		$block         = [ 'blockName' => 'core/post-featured-image' ];

		$result = $fallback->resetPostThumbnailFallback( $block_content, $block, $instance );

		$this->assertSame( $block_content, $result );
		$this->assertSame( [ 7 => 44 ], $fallback->getImageMap() );
	}

	private function createTemporaryPluginRoot(): string
	{
		$root = trailingslashit( sys_get_temp_dir() ) . 'fibf-tests-' . uniqid( '', true ) . '/';
		mkdir( $root . 'build', 0777, true );

		return $root;
	}

	private function createAssetsProvider( string $url, string $path ): Assets
	{
		$script_loader = $this->createScriptLoader( $url, $path );
		$style_loader  = $this->createStyleLoader( $url, $path );
		$assets        = new Assets( $script_loader, $style_loader );
		$assets->setPackage( 'featured_image_block_fallback' );

		return $assets;
	}

	private function createScriptLoader( string $url, string $path ): ScriptLoader
	{
		$loader = new ScriptLoader(
			new FilePathResolver( $path ),
			new UrlResolver( $url )
		);
		$loader->setPackage( 'featured_image_block_fallback' );

		return $loader;
	}

	private function createStyleLoader( string $url, string $path ): StyleLoader
	{
		$loader = new StyleLoader(
			new FilePathResolver( $path ),
			new UrlResolver( $url )
		);
		$loader->setPackage( 'featured_image_block_fallback' );

		return $loader;
	}

	private function createFallback(): Fallback
	{
		$fallback = new Fallback();
		$fallback->setPackage( 'featured_image_block_fallback' );

		return $fallback;
	}

	private function createInspectableFallback(): Fallback
	{
		$fallback = new class() extends Fallback {
			public function seedImageMap( array $map ): void
			{
				$this->image_map = $map;
			}

			public function getImageMap(): array
			{
				return $this->image_map;
			}
		};
		$fallback->setPackage( 'featured_image_block_fallback' );

		return $fallback;
	}
}
