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

namespace MarkedEffect\FeaturedImageBlockFallback;

use WP_Block_Type_Registry;

class Plugin {
    /**
     * URI of this plugin/package
     *
     * Used to enqueue block editor assets.
     * 
     * @var string
     */
    protected string $uri;
    /**
     * Mayber dont' need
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

    public function __construct(
        string $uri = '',
        string $path = ''
    ) {
        $this->setUri( ! empty( $uri ) ? $uri : $this->inferUri() );
        $this->setPath( ! empty( $path ) ? $path : dirname( __DIR__ ) );
        $this->init();
    }
    /**
     * Infer the URI of the plugin based on the WP_SITEURL and ABSPATH constants.
     * 
     * Used when the plugin is not loaded via the WordPress plugin system.
     *
     * @return string
     */
    protected function inferUri(): string
    {
        if ( ! defined( 'WP_SITEURL' ) || ! defined( 'ABSPATH' ) ) {
            return '';
        }

        $rel_path = str_replace( ABSPATH, '', dirname( __DIR__ ) );
        $uri = trailingslashit( WP_SITEURL ) . $rel_path;
        
        return trailingslashit( $uri );
    }
    /**
     * Setter for the URI property.
     *
     * @param string $uri string URI to set.
     *
     * @return void
     */
    protected function setUri( string $uri ): void
    {
        $this->uri = trailingslashit( $uri );
    }

    /**
     * Setter for the path property.
     *
     * @param string $path string path to set.
     *
     * @return void
     */
    protected function setPath( string $path ): void
    {
        $this->path = trailingslashit( $path );
    }
    /**
     * Initialize the plugin.
     *
     * @return void
     */
    protected function init(): void
    {
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueBlockAssets' ] );
        add_action( 'init', [ $this, 'registerBlockAttributes' ] );
        add_filter( 'pre_render_block', [ $this, 'preProcessBlock' ], 10, 2 );
    }
    /**
     * Enqueue block editor assets.
     *
     * @return void
     */
    public function enqueueBlockAssets(): void
    {
        $build_data = include $this->path . 'build/main.asset.php';

        wp_enqueue_style( 
            'featured-image-block-fallback', 
            $this->uri . 'build/main.css',
            [],
            $build_data['version'],
            'all'
        );

        wp_enqueue_script(
            'featured-image-block-fallback',
            $this->uri . 'build/main.js',
            $build_data['dependencies'],
            $build_data['version'],
            true
        );
    }
    /**
     * Add the custom attribute to store the fallback image ID.
     *
     * @return void
     */
    public function registerBlockAttributes(): void
    {
        $registry = WP_Block_Type_Registry::get_instance();

        if ( $registry->is_registered( 'core/post-featured-image' ) ) {
            
            $block = $registry->get_registered( 'core/post-featured-image' );
            
            $block->attributes['featuredImageFallback'] = [
                'type'    => 'object',
                'default' => [
                    'url'    => '',
                    'id'     => '',
                ],
            ];
        }
    }
    /**
     * Filters the post thumbnail ID.
     *
     * @param integer $thumbnail_id the default thumbnail ID.
     *
     * @return integer
     */
    public function filterPostThumbnailId( int $thumbnail_id ): int
    {
        if ( empty( $this->current_fallback_id ) ) {
            return $thumbnail_id;
        }

        $fallback_id = $this->current_fallback_id;

        $this->current_fallback_id = null;

        remove_filter( 'post_thumbnail_id', [ $this, 'filterPostThumbnailId' ] );

        return $fallback_id;
    }
    /**
     * Preprocess the block content.
     * 
     * Used to check the conditions, and conditionally add the necessary filter.
     *
     * @param string|null $block_content The block content.
     * @param array       $block The block attributes.
     *
     * @return string|null The (un)modified block content.
     */
    public function preProcessBlock( string|null $block_content, array $block ): ?string
    {
        if ( 
            $block['blockName'] !== 'core/post-featured-image'
            || has_post_thumbnail( get_the_id() )
        ) {
            return $block_content;
        }

        $fallback_id = apply_filters(
            'featured_image_block_fallback_id',
            intval( $block['attrs']['featuredImageFallback']['id'] ?? 0 ),
            $block
        );

        if ( ! empty( $$fallback_id ) ) {
            $this->current_fallback_id = intval( $block['attrs']['featuredImageFallback']['id'] );
            add_filter( 'post_thumbnail_id', [ $this, 'filterPostThumbnailId' ] );
        }
        return $block_content;
    }
}